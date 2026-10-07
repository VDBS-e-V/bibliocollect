<?php

declare(strict_types=1);

namespace App\Surfaces\Pos\Http\Controllers;

use App\Modules\Catalog\Actions\RecordCatalogIntakeAction;
use App\Modules\Catalog\Contracts\CatalogCoverProvider;
use App\Modules\Catalog\DTOs\BibliographicRecord;
use App\Modules\Catalog\DTOs\CatalogIntakeData;
use App\Modules\Catalog\DTOs\CatalogIntakeDetails;
use App\Modules\Catalog\DTOs\CatalogIntakeProvenance;
use App\Modules\Catalog\DTOs\CopyData;
use App\Modules\Catalog\Exceptions\DuplicateCopyBarcode;
use App\Modules\Catalog\Models\Edition;
use App\Modules\Catalog\Queries\CatalogImportMatchQuery;
use App\Modules\Catalog\Services\BibliographicLookupService;
use App\Modules\Catalog\Services\CatalogIsbnNormalizer;
use App\Modules\Catalog\Services\CatalogSignatureOptions;
use App\Modules\Catalog\Services\CatalogSummaryService;
use App\Surfaces\Pos\Http\Requests\CatalogIntakeBarcodeRequest;
use App\Surfaces\Pos\Http\Requests\CatalogIntakeChoiceRequest;
use App\Surfaces\Pos\Http\Requests\CatalogIntakeCommitRequest;
use App\Surfaces\Pos\Http\Requests\CatalogIntakeCopyRequest;
use App\Surfaces\Pos\Http\Requests\CatalogIntakeDetailsRequest;
use App\Surfaces\Pos\Http\Requests\CatalogIntakeLookupRequest;
use App\Surfaces\Pos\Support\CatalogIntakeDraft;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * Geführter Erfassungsprozess für neue Medien:
 * 1 Inventarnummer · 2 Medium suchen (ISBN oder Titel) · 3 Treffer prüfen · 4 Titel & Ausgabe · 5 Exemplar · 6 Prüfen & speichern.
 *
 * Bis zum ausdrücklichen Speichern in Schritt 5 wird nichts in den Katalog geschrieben.
 */
final class CatalogIntakeController
{
    public function __construct(
        private readonly CatalogIntakeDraft $draft,
        private readonly BibliographicLookupService $lookup,
        private readonly CatalogImportMatchQuery $matches,
        private readonly CatalogIsbnNormalizer $isbns,
        private readonly CatalogSummaryService $summaries,
    ) {}

    public function identify(Request $request): Response
    {
        if ($request->boolean('neu')) {
            $this->draft->clear();
        }

        return $this->view('identify', [
            'draftBarcode' => $this->draft->barcode(),
        ]);
    }

    /** Schritt 1 ist erledigt: Die Inventarnummer steht fest, weiter zur Suche nach dem Medium. */
    public function storeBarcode(CatalogIntakeBarcodeRequest $request): RedirectResponse
    {
        $this->draft->start($request->barcode(), ['isbn' => null, 'title' => null, 'person' => null]);

        return redirect()->route('pos.catalog.intake.medium');
    }

    public function medium(): Response|RedirectResponse
    {
        if (! $this->draft->exists()) {
            return redirect()->route('pos.catalog.intake.identify');
        }

        return $this->view('medium', ['draftQuery' => $this->draft->query()]);
    }

    public function lookup(CatalogIntakeLookupRequest $request): RedirectResponse
    {
        $barcode = $this->draft->barcode();

        if ($barcode === null) {
            return redirect()->route('pos.catalog.intake.identify');
        }

        $isbn = $request->isbn();

        $this->draft->start($barcode, [
            'isbn' => $isbn,
            'title' => $request->searchTitle(),
            'person' => $request->searchPerson(),
        ]);

        if (! $request->wantsLookup()) {
            $this->draft->setLookup([], true);
            $this->draft->chooseNew($this->blankDetails($isbn, null), null);

            return redirect()->route('pos.catalog.intake.details');
        }

        $result = $isbn !== null
            ? $this->lookup->byIsbn($isbn)
            : $this->lookup->search($request->searchTitle(), $request->searchPerson());

        $this->draft->setLookup($result->records, $result->available);

        $local = $isbn !== null ? $this->localEditions([$isbn]) : [];

        // Genau ein Treffer zur ISBN und noch nicht im Katalog: Schritt 2 entfällt.
        if ($isbn !== null && $local === [] && count($result->records) === 1) {
            $this->chooseHit($result->records[0]);

            return redirect()
                ->route('pos.catalog.intake.details')
                ->with('intake_notice', 'Die Daten wurden von der DNB übernommen. Bitte prüfen und bei Bedarf ergänzen.')
                ->with('intake_notice_variant', 'success');
        }

        if ($result->records === [] && $local === []) {
            $this->draft->chooseNew($this->blankDetails($isbn, $request->searchTitle()), null);

            return redirect()
                ->route('pos.catalog.intake.details')
                ->with('intake_notice', $result->available
                    ? 'Bei der DNB wurde dazu nichts gefunden. Bitte erfasse die Daten manuell.'
                    : 'Die DNB ist derzeit nicht erreichbar. Du kannst das Medium trotzdem manuell erfassen.')
                ->with('intake_notice_variant', 'warning');
        }

        return redirect()->route('pos.catalog.intake.matches');
    }

    public function matches(): Response|RedirectResponse
    {
        if (! $this->draft->exists()) {
            return redirect()->route('pos.catalog.intake.identify');
        }

        $hits = $this->draft->hits();
        $isbns = [];

        foreach ([$this->draft->query()['isbn'], ...array_map(static fn (BibliographicRecord $hit): ?string => $hit->isbn, $hits)] as $isbn) {
            if ($isbn !== null) {
                $isbns[] = $this->isbns->normalize($isbn);
            }
        }

        $localEditions = $this->localEditions($isbns);

        if ($hits === [] && $localEditions === []) {
            $this->draft->chooseNew($this->blankDetails($this->draft->query()['isbn'], null), null);

            return redirect()->route('pos.catalog.intake.details');
        }

        return $this->view('matches', [
            'hits' => $hits,
            'localEditions' => $localEditions,
            'lookupAvailable' => $this->draft->lookupAvailable(),
        ]);
    }

    public function choose(CatalogIntakeChoiceRequest $request): RedirectResponse
    {
        if (! $this->draft->exists()) {
            return redirect()->route('pos.catalog.intake.identify');
        }

        $choice = $request->choice();

        if (str_starts_with($choice, 'edition:')) {
            $edition = Edition::query()->find(substr($choice, 8));

            if ($edition === null) {
                return $this->invalidChoice();
            }

            $this->draft->chooseExistingEdition((string) $edition->getKey());

            return redirect()->route('pos.catalog.intake.copy');
        }

        if (str_starts_with($choice, 'hit:')) {
            $hit = $this->draft->hits()[(int) substr($choice, 4)] ?? null;

            if ($hit === null) {
                return $this->invalidChoice();
            }

            $this->chooseHit($hit);

            return redirect()->route('pos.catalog.intake.details');
        }

        $this->draft->chooseNew($this->blankDetails($this->draft->query()['isbn'], null), null);

        return redirect()->route('pos.catalog.intake.details');
    }

    public function details(): Response|RedirectResponse
    {
        $guard = $this->guardMode(CatalogIntakeDraft::MODE_NEW);

        if ($guard !== null) {
            return $guard;
        }

        return $this->view('details', [
            'summarySource' => $this->draft->summarySource(),
            'details' => $this->draft->detailsOrPrefill() ?? $this->blankDetails(null, null),
            'provenance' => $this->draft->provenance(),
            'hasHits' => $this->draft->hits() !== [],
        ]);
    }

    public function storeDetails(CatalogIntakeDetailsRequest $request): RedirectResponse
    {
        $guard = $this->guardMode(CatalogIntakeDraft::MODE_NEW);

        if ($guard !== null) {
            return $guard;
        }

        $this->draft->setDetails($request->toDetails());

        return redirect()->route('pos.catalog.intake.copy');
    }

    public function copy(): Response|RedirectResponse
    {
        $guard = $this->guardDetailsReady();

        if ($guard !== null) {
            return $guard;
        }

        return $this->view('copy', [
            'copy' => $this->draft->copy(),
            'context' => $this->context(),
            'signatureOptions' => app(CatalogSignatureOptions::class)->forSelect(),
        ]);
    }

    public function storeCopy(CatalogIntakeCopyRequest $request): RedirectResponse
    {
        $guard = $this->guardDetailsReady();

        if ($guard !== null) {
            return $guard;
        }

        $this->draft->setCopy(null, $request->status(), $request->signatureId());

        return redirect()->route('pos.catalog.intake.review');
    }

    public function review(CatalogCoverProvider $covers): Response|RedirectResponse
    {
        $guard = $this->guardCopyReady();

        if ($guard !== null) {
            return $guard;
        }

        return $this->view('review', [
            'copy' => $this->draft->copy(),
            'context' => $this->context(),
            'details' => $this->draft->details(),
            'provenance' => $this->draft->provenance(),
            'willFetchCover' => $covers->configured(),
        ]);
    }

    public function commit(CatalogIntakeCommitRequest $request, RecordCatalogIntakeAction $record): RedirectResponse
    {
        $guard = $this->guardCopyReady();
        $copy = $this->draft->copy();
        $barcode = $this->draft->barcode();

        if ($guard !== null || $copy === null || $barcode === null) {
            return $guard ?? redirect()->route('pos.catalog.intake.identify');
        }

        // Der Standort wird erst beim Einsortieren ins Regal vermerkt (Vorgang „Medien einsortieren“).
        $copyData = new CopyData($barcode, null, $copy['status'], $copy['signature_id']);
        $editionId = $this->draft->existingEditionId();
        $details = $this->draft->details();

        $data = $editionId !== null
            ? CatalogIntakeData::additionalCopy($editionId, $copyData)
            : ($details !== null ? CatalogIntakeData::newMedium($details, $this->draft->provenance(), $copyData) : null);

        if ($data === null) {
            return redirect()->route('pos.catalog.intake.details');
        }

        try {
            $result = $record->execute($data);
        } catch (DuplicateCopyBarcode) {
            return redirect()
                ->route('pos.catalog.intake.identify')
                ->withErrors(['barcode' => 'Diese Inventarnummer wurde inzwischen einem anderen Exemplar zugeordnet. Bitte vergib eine neue.']);
        } catch (ModelNotFoundException) {
            return redirect()
                ->route('pos.catalog.intake.identify')
                ->withErrors(['barcode' => 'Die gewählte Ausgabe existiert nicht mehr. Bitte starte die Erfassung neu.']);
        }

        $this->draft->clear();

        $message = $result->createdEdition
            ? sprintf('„%s“ wurde mit der Inventarnummer %s erfasst.', $result->title->preferred_title, $result->copy->barcode)
            : sprintf('Das Exemplar %s wurde zu „%s“ hinzugefügt.', $result->copy->barcode, $result->title->preferred_title);

        if ($request->wantsAnother()) {
            return redirect()->route('pos.catalog.intake.identify')->with('catalog_success', $message);
        }

        return redirect()
            ->route('pos.catalog.titles.show', ['titleId' => $result->title->getKey()])
            ->with('catalog_success', $message);
    }

    public function cancel(): RedirectResponse
    {
        $this->draft->clear();

        return redirect()->route('pos.catalog.index');
    }

    private function chooseHit(BibliographicRecord $hit): void
    {
        $details = CatalogIntakeDetails::fromRecord($hit);
        $summarySource = null;

        // Die DNB liefert selten einen Klappentext; wenn er fehlt, wird er zur ISBN nachgeschlagen (nur als Vorschlag).
        if ($details->summary === null && $details->isbn !== null) {
            $found = $this->summaries->findByIsbn($details->isbn);

            if ($found !== null) {
                $details = CatalogIntakeDetails::fromArray([...$details->toArray(), 'summary' => $found['text']]);
                $summarySource = $found['source'];
            }
        }

        $this->draft->chooseNew($details, CatalogIntakeProvenance::fromRecord($hit));
        $this->draft->setSummarySource($summarySource);
    }

    /** Vorbelegung für die manuelle Erfassung (deutschsprachiges Buch ist der Regelfall der Schulbibliothek). */
    private function blankDetails(?string $isbn, ?string $title): CatalogIntakeDetails
    {
        return CatalogIntakeDetails::fromArray([
            'preferred_title' => $title,
            'isbn' => $isbn,
            'media_type' => 'book',
            'language_code' => 'de',
        ]);
    }

    /**
     * Vorhandene Ausgaben zu den ISBNs, jeweils einmal, mit Titel und Exemplarzahl.
     *
     * @param  list<string>  $isbns
     * @return list<Edition>
     */
    private function localEditions(array $isbns): array
    {
        $ids = [];

        foreach ($this->matches->editionsByIsbns(array_values(array_unique($isbns))) as $editions) {
            foreach ($editions as $edition) {
                $ids[(string) $edition->getKey()] = true;
            }
        }

        if ($ids === []) {
            return [];
        }

        /** @var Collection<int, Edition> $editions */
        $editions = Edition::query()
            ->with('title')
            ->withCount('copies')
            ->whereIn('id', array_keys($ids))
            ->get();

        return $editions->values()->all();
    }

    /**
     * Titel und Ausgabe, auf die sich Exemplar-Schritt und Zusammenfassung beziehen.
     *
     * @return array{existing: Edition|null, title: string|null, subtitle: string|null}
     */
    private function context(): array
    {
        $editionId = $this->draft->existingEditionId();

        if ($editionId !== null) {
            $edition = Edition::query()->with('title')->find($editionId);

            return [
                'existing' => $edition,
                'title' => $edition?->title->preferred_title,
                'subtitle' => $edition?->title->subtitle,
            ];
        }

        $details = $this->draft->details();

        return [
            'existing' => null,
            'title' => $details?->preferredTitle,
            'subtitle' => $details?->subtitle,
        ];
    }

    private function guardMode(string $mode): ?RedirectResponse
    {
        if (! $this->draft->exists()) {
            return redirect()->route('pos.catalog.intake.identify');
        }

        return match ($this->draft->mode()) {
            $mode => null,
            CatalogIntakeDraft::MODE_EXISTING => redirect()->route('pos.catalog.intake.copy'),
            default => redirect()->route('pos.catalog.intake.matches'),
        };
    }

    private function guardDetailsReady(): ?RedirectResponse
    {
        if (! $this->draft->exists()) {
            return redirect()->route('pos.catalog.intake.identify');
        }

        if (! $this->draft->detailsReady()) {
            return $this->draft->mode() === CatalogIntakeDraft::MODE_NEW
                ? redirect()->route('pos.catalog.intake.details')
                : redirect()->route('pos.catalog.intake.matches');
        }

        return null;
    }

    private function guardCopyReady(): ?RedirectResponse
    {
        $guard = $this->guardDetailsReady();

        if ($guard !== null) {
            return $guard;
        }

        return $this->draft->copy() === null ? redirect()->route('pos.catalog.intake.copy') : null;
    }

    private function invalidChoice(): RedirectResponse
    {
        return redirect()
            ->route('pos.catalog.intake.matches')
            ->withErrors(['choice' => 'Diese Auswahl ist nicht mehr gültig. Bitte wähle erneut.']);
    }

    /** @param array<string, mixed> $data */
    private function view(string $step, array $data): Response
    {
        return response()
            ->view('pages.surfaces.pos.catalog.intake.'.$step, [
                'barcode' => $this->draft->barcode(),
                'mode' => $this->draft->mode(),
                'detailsSkipped' => $this->draft->mode() === CatalogIntakeDraft::MODE_EXISTING,
                ...$data,
            ])
            ->header('Cache-Control', 'private, no-store');
    }
}
