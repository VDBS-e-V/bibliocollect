<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Actions;

use App\Modules\Catalog\Contracts\CatalogCoverProvider;
use App\Modules\Catalog\DTOs\CatalogIntakeData;
use App\Modules\Catalog\DTOs\CatalogIntakeDetails;
use App\Modules\Catalog\DTOs\CatalogIntakeProvenance;
use App\Modules\Catalog\DTOs\CatalogIntakeResult;
use App\Modules\Catalog\Exceptions\DuplicateCopyBarcode;
use App\Modules\Catalog\Jobs\RefreshEditionCoverJob;
use App\Modules\Catalog\Models\Contributor;
use App\Modules\Catalog\Models\Edition;
use App\Modules\Catalog\Models\Title;
use App\Modules\Catalog\Models\TitleContribution;
use App\Modules\Catalog\Services\CatalogIsbnNormalizer;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Speichert einen bestätigten Erfassungsvorgang vollständig oder gar nicht:
 * neues Medium (Titel, Verantwortliche, Ausgabe, Exemplar) oder weiteres Exemplar zu einer Ausgabe.
 */
final readonly class RecordCatalogIntakeAction
{
    public function __construct(
        private CreateCopyAction $createCopy,
        private CatalogIsbnNormalizer $isbns,
        private CatalogCoverProvider $covers,
    ) {}

    /**
     * @throws DuplicateCopyBarcode
     * @throws ModelNotFoundException wenn die gewählte vorhandene Ausgabe inzwischen entfernt wurde
     */
    public function execute(CatalogIntakeData $data): CatalogIntakeResult
    {
        $result = DB::transaction(function () use ($data): CatalogIntakeResult {
            if ($data->existingEditionId !== null) {
                /** @var Edition $edition */
                $edition = Edition::query()->with('title')->findOrFail($data->existingEditionId);
                $copy = $this->createCopy->execute($edition, $data->copy);

                return new CatalogIntakeResult($edition->title, $edition, $copy, false);
            }

            $details = $data->details
                ?? throw new InvalidArgumentException('Für ein neues Medium werden Titel- und Ausgabedaten benötigt.');

            $title = $this->createTitle($details);
            $this->attachContributors($title, $details->contributors);
            $edition = $this->createEdition($title, $details, $data->provenance);
            $copy = $this->createCopy->execute($edition, $data->copy);

            return new CatalogIntakeResult($title, $edition, $copy, true);
        });

        if ($result->createdEdition) {
            $this->queueCoverDownload($result->edition);
        }

        return $result;
    }

    private function createTitle(CatalogIntakeDetails $details): Title
    {
        return Title::query()->create([
            'preferred_title' => $details->preferredTitle,
            'subtitle' => $details->subtitle,
            'sort_title' => null,
        ]);
    }

    private function createEdition(Title $title, CatalogIntakeDetails $details, ?CatalogIntakeProvenance $provenance): Edition
    {
        /** @var Edition $edition */
        $edition = $title->editions()->create([
            'edition_statement' => $details->editionStatement,
            'isbn' => $details->isbn !== null ? $this->isbns->normalize($details->isbn) : null,
            'publisher_name' => $details->publisherName,
            'publication_year' => $details->publicationYear,
            'media_type' => $details->mediaType,
            'language_code' => $details->languageCode,
            'minimum_age' => $details->minimumAge,
            'responsibility_statement' => $details->responsibilityStatement,
            'series_statement' => $details->seriesStatement,
            'publication_place' => $details->publicationPlace,
            'local_classification' => $details->localClassification,
            'original_language_code' => $details->originalLanguageCode,
            'physical_extent' => $details->physicalExtent,
            'summary' => $details->summary,
            'subject_keywords' => $details->subjectKeywords,
            'target_audience' => $details->targetAudience,
            'metadata_source' => $provenance?->source,
            'source_record_id' => $provenance?->recordId,
            'source_permalink' => $provenance?->permalink,
        ]);

        return $edition;
    }

    /** @param list<array{name: string, role: string, gnd_id: string|null}> $contributors */
    private function attachContributors(Title $title, array $contributors): void
    {
        $position = 0;

        foreach ($contributors as $entry) {
            $contributor = $this->contributorFor($entry['name'], $entry['gnd_id']);

            $alreadyLinked = TitleContribution::query()
                ->where('title_id', $title->getKey())
                ->where('contributor_id', $contributor->getKey())
                ->where('role_key', $entry['role'])
                ->exists();

            if ($alreadyLinked) {
                continue;
            }

            $position++;
            $title->contributions()->create([
                'contributor_id' => $contributor->getKey(),
                'role_key' => $entry['role'],
                'position' => $position,
            ]);
        }
    }

    /**
     * Verantwortliche werden wiederverwendet, statt Dubletten zu erzeugen: zuerst über die GND-ID
     * (eindeutig), sonst nur bei genau einem exakten Namenstreffer ohne widersprüchliche GND-ID.
     */
    private function contributorFor(string $name, ?string $gndId): Contributor
    {
        if ($gndId !== null) {
            $byGnd = Contributor::query()->where('gnd_id', $gndId)->first();

            if ($byGnd !== null) {
                return $byGnd;
            }
        }

        $sortName = str_contains($name, ',') ? $name : null;
        $displayName = $this->displayName($name);

        $candidates = Contributor::query()
            ->where('display_name', $displayName)
            ->where('sort_name', $sortName)
            ->get()
            ->filter(static fn (Contributor $candidate): bool => $candidate->gnd_id === null || $candidate->gnd_id === $gndId);

        if ($candidates->count() === 1) {
            /** @var Contributor $existing */
            $existing = $candidates->first();

            if ($existing->gnd_id === null && $gndId !== null) {
                $existing->forceFill(['gnd_id' => $gndId])->save();
            }

            return $existing;
        }

        /** @var Contributor $contributor */
        $contributor = Contributor::query()->create([
            'display_name' => $displayName,
            'sort_name' => $sortName,
            'gnd_id' => $gndId,
        ]);

        return $contributor;
    }

    /** "Nachname, Vorname" wird zur Anzeigeform "Vorname Nachname"; alles andere bleibt unverändert. */
    private function displayName(string $name): string
    {
        $parts = array_map('trim', explode(',', $name));

        if (count($parts) === 2 && $parts[0] !== '' && $parts[1] !== '') {
            return $parts[1].' '.$parts[0];
        }

        return trim($name);
    }

    private function queueCoverDownload(Edition $edition): void
    {
        if ($edition->isbn === null || $edition->isbn === '' || ! $this->covers->configured()) {
            return;
        }

        $edition->forceFill(['cover_status' => 'pending'])->save();
        RefreshEditionCoverJob::dispatch((string) $edition->getKey());
    }
}
