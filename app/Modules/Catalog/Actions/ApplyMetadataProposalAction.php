<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Actions;

use App\Modules\Audit\Services\AuditRecorder;
use App\Modules\Catalog\DTOs\MetadataChange;
use App\Modules\Catalog\DTOs\MetadataProposal;
use App\Modules\Catalog\Enums\MetadataReviewStatus;
use App\Modules\Catalog\Exceptions\MetadataProposalOutdated;
use App\Modules\Catalog\Models\CatalogMetadataReview;
use App\Modules\Catalog\Models\Contributor;
use App\Modules\Catalog\Models\Edition;
use App\Modules\Catalog\Models\Title;
use App\Modules\Catalog\Models\TitleContribution;
use App\Modules\Catalog\Quality\CatalogMetadataAssessor;
use App\Modules\Catalog\Quality\MetadataFields;
use App\Modules\Catalog\Quality\MetadataFingerprint;
use App\Modules\Catalog\Services\ContributorResolver;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Übernimmt die ausgewählten Änderungen eines Vorschlags. Alles oder nichts, in einer Transaktion.
 *
 * Es wird ausschließlich der im Vorschlag gespeicherte Wert geschrieben, nie ein Wert aus der Anfrage.
 * Die Auswahl kann also nur "an" oder "aus" bedeuten, und nur für Felder aus {@see MetadataFields}.
 */
final readonly class ApplyMetadataProposalAction
{
    public function __construct(
        private MetadataFingerprint $fingerprint,
        private CatalogMetadataAssessor $assessor,
        private ContributorResolver $contributors,
        private AuditRecorder $audit,
    ) {}

    /**
     * @param  list<string>  $selectedKeys
     *
     * @throws MetadataProposalOutdated wenn kein Vorschlag vorliegt oder sich die Ausgabe seitdem geändert hat
     * @throws InvalidArgumentException bei leerer oder unbekannter Auswahl
     */
    public function execute(CatalogMetadataReview $review, array $selectedKeys, int $userId): CatalogMetadataReview
    {
        if ($selectedKeys === []) {
            throw new InvalidArgumentException('Es wurde keine Änderung ausgewählt.');
        }

        return DB::transaction(function () use ($review, $selectedKeys, $userId): CatalogMetadataReview {
            /** @var CatalogMetadataReview $locked */
            $locked = CatalogMetadataReview::query()->whereKey($review->getKey())->lockForUpdate()->firstOrFail();
            /** @var Edition $edition */
            $edition = Edition::query()->whereKey($locked->edition_id)->lockForUpdate()->firstOrFail();
            $edition->load('title.contributions.contributor');

            if ($locked->proposal === null) {
                throw MetadataProposalOutdated::missing();
            }

            $proposal = MetadataProposal::fromArray($locked->proposal);

            if ($proposal->fingerprint !== $this->fingerprint->for($edition)) {
                throw MetadataProposalOutdated::changed();
            }

            $changes = $this->selectedChanges($proposal, $selectedKeys);
            $applied = [];

            foreach ($changes as $change) {
                $applied[] = match ($change->kind) {
                    MetadataChange::ADD => $this->addContributor($edition->title, $change),
                    MetadataChange::RENAME => $this->renameContributor($change),
                    default => $this->writeField($edition, $change),
                };
            }

            $this->adoptProvenance($edition, $proposal);
            $edition->title->save();
            $edition->save();

            $fresh = Edition::query()->with('title.contributions.contributor')->findOrFail($edition->getKey());
            $issues = $this->assessor->assessEdition($fresh);

            $locked->forceFill([
                'status' => $issues === [] ? MetadataReviewStatus::Resolved : MetadataReviewStatus::Open,
                'issues' => array_map(static fn ($issue): string => $issue->value, $issues),
                'severity' => $this->assessor->severity($issues),
                'fingerprint' => $this->fingerprint->for($fresh),
                'proposal' => null,
                'proposal_source' => null,
                'proposal_state' => null,
                'proposal_fetched_at' => null,
                'decided_by_user_id' => $userId,
                'decided_at' => now(),
                'history' => [...($locked->history ?? []), [
                    'at' => now()->toIso8601String(),
                    'user_id' => $userId,
                    'action' => 'applied',
                    'source' => $proposal->source,
                    'record_id' => $proposal->recordId,
                    'changes' => $applied,
                ]],
            ])->save();

            $this->audit->record(
                'catalog.metadata.applied',
                count($applied).' Metadatenänderung(en) aus einem Vorschlag übernommen.',
                $edition,
                ['source' => $proposal->source, 'fields' => implode(',', array_column($applied, 'key'))],
                $userId,
            );

            return $locked;
        });
    }

    /**
     * @param  list<string>  $selectedKeys
     * @return list<MetadataChange>
     */
    private function selectedChanges(MetadataProposal $proposal, array $selectedKeys): array
    {
        $changes = [];

        foreach (array_unique($selectedKeys) as $key) {
            $change = $proposal->change($key);

            if ($change === null || ($change->proposed === null && $change->kind !== MetadataChange::LOCAL)) {
                throw new InvalidArgumentException('Die Auswahl enthält eine Änderung, die nicht im Vorschlag steht.');
            }

            $changes[] = $change;
        }

        return $changes;
    }

    /** @return array{key: string, label: string, from: string|null, to: string|null} */
    private function writeField(Edition $edition, MetadataChange $change): array
    {
        $field = MetadataFields::all()[$change->key] ?? null;

        if ($field === null) {
            throw new InvalidArgumentException('Unbekanntes Feld: '.$change->key);
        }

        $value = $change->proposed;

        if ($change->key === 'edition.publication_year') {
            $value = $value !== null && ctype_digit($value) ? (string) (int) $value : null;
        }

        $model = $field['target'] === MetadataFields::TITLE ? $edition->title : $edition;
        $from = $model->getAttribute($field['column']);
        $model->setAttribute($field['column'], $field['column'] === 'publication_year' && $value !== null ? (int) $value : $value);

        return $this->logEntry($change, is_scalar($from) ? (string) $from : null, $value);
    }

    /** @return array{key: string, label: string, from: string|null, to: string|null} */
    private function addContributor(Title $title, MetadataChange $change): array
    {
        $name = is_string($change->payload['name'] ?? null) ? $change->payload['name'] : null;
        $role = is_string($change->payload['role'] ?? null) ? $change->payload['role'] : 'contributor';
        $gnd = is_string($change->payload['gnd_id'] ?? null) ? $change->payload['gnd_id'] : null;

        if ($name === null || trim($name) === '') {
            throw new InvalidArgumentException('Der Vorschlag enthält keinen Namen.');
        }

        $contributor = $this->contributors->resolve($name, $gnd);

        $linked = TitleContribution::query()
            ->where('title_id', $title->getKey())
            ->where('contributor_id', $contributor->getKey())
            ->where('role_key', $role)
            ->exists();

        if (! $linked) {
            $position = (int) TitleContribution::query()->where('title_id', $title->getKey())->max('position') + 1;
            $title->contributions()->create([
                'contributor_id' => $contributor->getKey(),
                'role_key' => $role,
                'position' => $position,
            ]);
        }

        return $this->logEntry($change, null, $change->proposed);
    }

    /** @return array{key: string, label: string, from: string|null, to: string|null} */
    private function renameContributor(MetadataChange $change): array
    {
        $id = is_string($change->payload['contributor_id'] ?? null) ? $change->payload['contributor_id'] : null;
        $display = is_string($change->payload['display_name'] ?? null) ? $change->payload['display_name'] : null;

        if ($id === null || $display === null || trim($display) === '') {
            throw new InvalidArgumentException('Der Vorschlag enthält keinen gültigen Namen.');
        }

        /** @var Contributor $contributor */
        $contributor = Contributor::query()->whereKey($id)->lockForUpdate()->firstOrFail();

        $attributes = [
            'display_name' => $display,
            'sort_name' => is_string($change->payload['sort_name'] ?? null) ? $change->payload['sort_name'] : null,
        ];

        $gnd = is_string($change->payload['gnd_id'] ?? null) ? $change->payload['gnd_id'] : null;

        // Die GND-ID ist eindeutig; sie wird nur gesetzt, wenn die Person noch keine hat und sie frei ist.
        if ($gnd !== null && $contributor->gnd_id === null && ! Contributor::query()->where('gnd_id', $gnd)->exists()) {
            $attributes['gnd_id'] = $gnd;
        }

        $contributor->forceFill($attributes)->save();

        return $this->logEntry($change, $change->current, $change->proposed);
    }

    /** Herkunft nur ergänzen, wenn sie fehlt; eine vorhandene Quellenangabe bleibt unverändert. */
    private function adoptProvenance(Edition $edition, MetadataProposal $proposal): void
    {
        if ($proposal->source === MetadataProposal::SOURCE_LOCAL || $proposal->recordId === null) {
            return;
        }

        if (blank($edition->metadata_source)) {
            $edition->metadata_source = 'dnb';
        }

        if (blank($edition->source_record_id)) {
            $edition->source_record_id = $proposal->recordId;
        }

        if (blank($edition->source_permalink) && $proposal->permalink !== null) {
            $edition->source_permalink = $proposal->permalink;
        }
    }

    /** @return array{key: string, label: string, from: string|null, to: string|null} */
    private function logEntry(MetadataChange $change, ?string $from, ?string $to): array
    {
        return ['key' => $change->key, 'label' => $change->label, 'from' => $from, 'to' => $to];
    }
}
