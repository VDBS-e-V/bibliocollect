<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Actions;

use App\Modules\Catalog\DTOs\LegacyCatalogImportReport;
use App\Modules\Catalog\Enums\CopyStatus;
use App\Modules\Catalog\Exceptions\LegacyCatalogImportException;
use App\Modules\Catalog\Legacy\LegacyCatalogImportAnalyzer;
use App\Modules\Catalog\Legacy\LegacyCatalogNormalizer;
use App\Modules\Catalog\Legacy\PhpMyAdminJsonTableReader;
use App\Modules\Catalog\Models\CatalogSignature;
use App\Modules\Catalog\Models\CatalogTopic;
use App\Modules\Catalog\Models\Contributor;
use App\Modules\Catalog\Models\Copy;
use App\Modules\Catalog\Models\Edition;
use App\Modules\Catalog\Models\Title;
use App\Modules\Catalog\Models\TitleContribution;
use Illuminate\Support\Facades\DB;

final readonly class ImportLegacyCatalogAction
{
    public function __construct(
        private PhpMyAdminJsonTableReader $reader,
        private LegacyCatalogNormalizer $normalizer,
        private LegacyCatalogImportAnalyzer $analyzer,
    ) {}

    public function execute(
        string $mediaPath,
        ?string $topicsPath = null,
        ?string $signaturesPath = null,
    ): LegacyCatalogImportReport {
        $analysis = $this->analyzer->analyze($mediaPath, $topicsPath, $signaturesPath);

        if ($analysis->hasConflicts()) {
            throw new LegacyCatalogImportException(
                'Legacy-Import wurde wegen '.count($analysis->conflicts).' Konflikt(en) nicht gestartet.',
            );
        }

        $mediaRows = $this->reader->read($mediaPath, 'mediaList');
        $topicRows = $topicsPath !== null ? $this->reader->read($topicsPath, 'mediaTopicList') : [];
        $signatureRows = $signaturesPath !== null ? $this->reader->read($signaturesPath, 'mediaSignatures') : [];

        $counts = DB::transaction(function () use ($mediaRows, $topicRows, $signatureRows): array {
            $counts = [
                'created_topics' => 0,
                'reused_topics' => 0,
                'created_signatures' => 0,
                'reused_signatures' => 0,
                'created_titles' => 0,
                'reused_titles' => 0,
                'created_editions' => 0,
                'reused_editions' => 0,
                'created_contributors' => 0,
                'reused_contributors' => 0,
                'created_contributions' => 0,
                'reused_contributions' => 0,
                'created_copies' => 0,
                'reused_copies' => 0,
                'relinked_copies' => 0,
                'resolved_editions' => 0,
            ];

            $topicsByLegacyId = $this->importTopics($topicRows, $counts);
            $signaturesByValue = $this->importSignatures($signatureRows, $topicsByLegacyId, $counts);
            /** @var array<string, Edition> $editionsByKey */
            $editionsByKey = [];
            /** @var array<string, Title> $titlesByKey */
            $titlesByKey = [];
            /** @var array<string, Contributor> $contributorsByKey */
            $contributorsByKey = [];
            /** @var array<string, true> $plannedEditionKeys */
            $plannedEditionKeys = [];
            /** @var array<string, true> $resolvedEditionIds */
            $resolvedEditionIds = [];

            foreach ($mediaRows as $row) {
                $record = $this->normalizer->media($row);

                if ($record['errors'] !== []) {
                    throw new LegacyCatalogImportException(implode(' ', $record['errors']));
                }

                $plannedEditionKeys[(string) $record['edition_key']] = true;
                $edition = $this->resolveEdition($record, $titlesByKey, $editionsByKey, $counts);
                $this->resolveContributors($record, $edition->title, $contributorsByKey, $counts);
                $copy = $this->resolveCopy($record, $edition, $signaturesByValue, $counts);
                $resolvedEditionIds[(string) $copy->edition_id] = true;
            }

            $counts['resolved_editions'] = count($resolvedEditionIds);

            if (count($plannedEditionKeys) !== count($resolvedEditionIds)) {
                throw new LegacyCatalogImportException(sprintf(
                    'Legacy-Import würde %d Editionsgruppen auf nur %d Editionen abbilden.',
                    count($plannedEditionKeys),
                    count($resolvedEditionIds),
                ));
            }

            return $counts;
        }, 3);

        return new LegacyCatalogImportReport(
            summary: array_merge($analysis->summary, $counts),
            warnings: $analysis->warnings,
            conflicts: [],
        );
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     * @param  array<string, int>  $counts
     * @return array<string, CatalogTopic>
     */
    private function importTopics(array $rows, array &$counts): array
    {
        $byLegacyId = [];

        foreach ($rows as $row) {
            $legacyId = trim((string) ($row['id'] ?? ''));

            if ($legacyId === '') {
                continue;
            }

            $topic = CatalogTopic::query()
                ->where('legacy_source', LegacyCatalogNormalizer::SOURCE)
                ->where('legacy_id', $legacyId)
                ->first();

            if ($topic === null) {
                $topic = CatalogTopic::query()->create([
                    'legacy_source' => LegacyCatalogNormalizer::SOURCE,
                    'legacy_id' => $legacyId,
                    'public_key' => $this->nullableString($row['public_topic_id'] ?? null),
                    'parent_id' => null,
                    'name' => trim((string) ($row['topic'] ?? '')),
                    'description' => $this->nullableString($row['description'] ?? null),
                ]);
                $counts['created_topics']++;
            } else {
                $counts['reused_topics']++;
            }

            $byLegacyId[$legacyId] = $topic;
        }

        foreach ($rows as $row) {
            $legacyId = trim((string) ($row['id'] ?? ''));
            $parentId = trim((string) ($row['main_topic'] ?? ''));
            $topic = $byLegacyId[$legacyId] ?? null;
            $parent = $byLegacyId[$parentId] ?? null;

            if ($topic !== null && $parent !== null && $topic->parent_id === null && ! $topic->is($parent)) {
                $topic->forceFill(['parent_id' => $parent->getKey()])->save();
            }
        }

        return $byLegacyId;
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     * @param  array<string, CatalogTopic>  $topicsByLegacyId
     * @param  array<string, int>  $counts
     * @return array<string, CatalogSignature>
     */
    private function importSignatures(array $rows, array $topicsByLegacyId, array &$counts): array
    {
        $byValue = [];

        foreach ($rows as $row) {
            $legacyId = trim((string) ($row['id'] ?? ''));
            $value = trim((string) ($row['signature'] ?? ''));

            if ($legacyId === '' || $value === '') {
                continue;
            }

            $signature = CatalogSignature::query()
                ->where('legacy_source', LegacyCatalogNormalizer::SOURCE)
                ->where('legacy_id', $legacyId)
                ->first();

            if ($signature === null) {
                $signature = CatalogSignature::query()->firstOrCreate(
                    ['signature' => $value],
                    [
                        'legacy_source' => LegacyCatalogNormalizer::SOURCE,
                        'legacy_id' => $legacyId,
                    ],
                );
                $counts[$signature->wasRecentlyCreated ? 'created_signatures' : 'reused_signatures']++;
            } else {
                $counts['reused_signatures']++;
            }

            $sync = [];

            foreach ($this->topicIds($row['topic_ids'] ?? null) as $position => $topicId) {
                $topic = $topicsByLegacyId[$topicId] ?? null;

                if ($topic !== null) {
                    $sync[(string) $topic->getKey()] = ['position' => $position + 1];
                }
            }

            if ($sync !== []) {
                $signature->topics()->syncWithoutDetaching($sync);
            }

            $byValue[$value] = $signature;
        }

        return $byValue;
    }

    /**
     * @param  array<string, mixed>  $record
     * @param  array<string, Title>  $titlesByKey
     * @param  array<string, Edition>  $editionsByKey
     * @param  array<string, int>  $counts
     */
    private function resolveEdition(array $record, array &$titlesByKey, array &$editionsByKey, array &$counts): Edition
    {
        $key = (string) $record['edition_key'];

        if (isset($editionsByKey[$key])) {
            return $editionsByKey[$key];
        }

        $existing = Edition::query()
            ->with('title')
            ->where('legacy_source', LegacyCatalogNormalizer::SOURCE)
            ->where('legacy_record_key', $key)
            ->first();

        if ($existing !== null) {
            $editionsByKey[$key] = $existing;
            $counts['reused_editions']++;

            return $existing;
        }

        $title = $this->resolveTitle($record['title'], $titlesByKey, $counts);
        $editionData = $record['edition'];
        $isbn = $editionData['isbn'] ?? null;

        if (is_string($isbn) && $isbn !== '') {
            $isbnMatches = Edition::query()
                ->where('title_id', $title->getKey())
                ->where('isbn', $isbn)
                ->get()
                ->filter(fn (Edition $candidate): bool => $this->canReuseForLegacyRecordKey($candidate, $key))
                ->values();

            if ($isbnMatches->count() === 1) {
                /** @var Edition $matched */
                $matched = $isbnMatches->first();

                if ($this->editionIsCompatible($matched, $editionData)) {
                    $this->fillMissingEditionMetadata($matched, $editionData);
                    $editionsByKey[$key] = $matched;
                    $counts['reused_editions']++;

                    return $matched;
                }
            }
        }

        /** @var Edition $edition */
        $edition = $title->editions()->create($editionData);
        $editionsByKey[$key] = $edition;
        $counts['created_editions']++;

        return $edition;
    }

    /**
     * @param  array{preferred_title:string,subtitle:?string,sort_title:?string}  $data
     * @param  array<string, Title>  $cache
     * @param  array<string, int>  $counts
     */
    private function resolveTitle(array $data, array &$cache, array &$counts): Title
    {
        $key = $data['preferred_title']."\0".($data['subtitle'] ?? '');

        if (isset($cache[$key])) {
            return $cache[$key];
        }

        $query = Title::query()->where('preferred_title', $data['preferred_title']);
        $data['subtitle'] === null
            ? $query->whereNull('subtitle')
            : $query->where('subtitle', $data['subtitle']);
        $title = $query->first();

        if ($title === null) {
            $title = Title::query()->create($data);
            $counts['created_titles']++;
        } else {
            $counts['reused_titles']++;
        }

        $cache[$key] = $title;

        return $title;
    }

    /**
     * @param  array<string, mixed>  $record
     * @param  array<string, Contributor>  $cache
     * @param  array<string, int>  $counts
     */
    private function resolveContributors(array $record, Title $title, array &$cache, array &$counts): void
    {
        foreach ($record['contributors'] as $data) {
            $cacheKey = $data['gnd_id'] !== null
                ? 'gnd:'.$data['gnd_id']
                : 'name:'.mb_strtolower($data['name']);
            $contributor = $cache[$cacheKey] ?? null;

            if ($contributor === null && $data['gnd_id'] !== null) {
                $contributor = Contributor::query()->where('gnd_id', $data['gnd_id'])->first();

                if ($contributor === null) {
                    $nameMatch = Contributor::query()->where('display_name', $data['name'])->first();

                    if ($nameMatch !== null && ($nameMatch->gnd_id === null || $nameMatch->gnd_id === $data['gnd_id'])) {
                        $contributor = $nameMatch;
                    }
                }
            } elseif ($contributor === null) {
                $contributor = Contributor::query()->where('display_name', $data['name'])->first();
            }

            if ($contributor === null) {
                $contributor = Contributor::query()->create([
                    'display_name' => $data['name'],
                    'sort_name' => $data['sort_name'],
                    'gnd_id' => $data['gnd_id'],
                ]);
                $counts['created_contributors']++;
            } else {
                if ($contributor->gnd_id === null && $data['gnd_id'] !== null) {
                    $contributor->forceFill(['gnd_id' => $data['gnd_id']])->save();
                }
                $counts['reused_contributors']++;
            }

            $cache[$cacheKey] = $contributor;

            $contribution = TitleContribution::query()->firstOrCreate(
                [
                    'title_id' => $title->getKey(),
                    'contributor_id' => $contributor->getKey(),
                    'role_key' => $data['role_key'],
                ],
                ['position' => $data['position']],
            );
            $counts[$contribution->wasRecentlyCreated ? 'created_contributions' : 'reused_contributions']++;
        }
    }

    /**
     * @param  array<string, mixed>  $record
     * @param  array<string, CatalogSignature>  $signaturesByValue
     * @param  array<string, int>  $counts
     */
    private function resolveCopy(array $record, Edition $edition, array $signaturesByValue, array &$counts): Copy
    {
        $data = $record['copy'];
        $legacyMediaId = $data['legacy_media_id'];
        $copy = null;

        if (is_string($legacyMediaId) && $legacyMediaId !== '') {
            $copy = Copy::query()
                ->where('legacy_source', LegacyCatalogNormalizer::SOURCE)
                ->where('legacy_media_id', $legacyMediaId)
                ->first();
        }

        if ($copy !== null) {
            if ((string) $copy->edition_id !== (string) $edition->getKey()) {
                $copy->forceFill(['edition_id' => $edition->getKey()])->save();
                $counts['relinked_copies']++;
            }

            $counts['reused_copies']++;

            return $copy;
        }

        $signatureValue = $data['signature'];
        $signature = is_string($signatureValue) ? ($signaturesByValue[$signatureValue] ?? null) : null;
        unset($data['signature']);
        $data['signature_id'] = $signature?->getKey();
        $data['status'] = $data['status'] instanceof CopyStatus ? $data['status'] : CopyStatus::Active;

        /** @var Copy $copy */
        $copy = $edition->copies()->create($data);
        $counts['created_copies']++;

        return $copy;
    }

    private function canReuseForLegacyRecordKey(Edition $edition, string $key): bool
    {
        if ($edition->legacy_source !== LegacyCatalogNormalizer::SOURCE) {
            return true;
        }

        return $edition->legacy_record_key === $key;
    }

    /** @param array<string, mixed> $data */
    private function editionIsCompatible(Edition $edition, array $data): bool
    {
        foreach ([
            'edition_statement',
            'edition_number',
            'publisher_name',
            'publication_year',
            'media_type',
            'language_code',
            'source_record_id',
        ] as $field) {
            $existing = $edition->getAttribute($field);
            $incoming = $data[$field] ?? null;

            if ($existing === null || $incoming === null) {
                continue;
            }

            if ((string) $existing !== (string) $incoming) {
                return false;
            }
        }

        return true;
    }

    /** @param array<string, mixed> $data */
    private function fillMissingEditionMetadata(Edition $edition, array $data): void
    {
        $updates = [];

        foreach ($data as $field => $value) {
            if (in_array($field, ['legacy_source', 'legacy_record_key'], true)) {
                continue;
            }

            if ($value !== null && $edition->getAttribute($field) === null) {
                $updates[$field] = $value;
            }
        }

        if ($edition->legacy_source === null) {
            $updates['legacy_source'] = LegacyCatalogNormalizer::SOURCE;
            $updates['legacy_record_key'] = $data['legacy_record_key'] ?? null;
        }

        if ($updates !== []) {
            $edition->forceFill($updates)->save();
        }
    }

    /** @return list<string> */
    private function topicIds(mixed $value): array
    {
        $data = is_array($value) ? $value : (is_string($value) ? json_decode($value, true) : null);

        if (! is_array($data)) {
            return [];
        }

        $result = [];

        foreach ($data as $id) {
            if (is_scalar($id) && trim((string) $id) !== '') {
                $result[] = trim((string) $id);
            }
        }

        return array_values(array_unique($result));
    }

    private function nullableString(mixed $value): ?string
    {
        if (! is_scalar($value)) {
            return null;
        }

        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }
}
