<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Import\Classification;

use App\Modules\Catalog\Exceptions\LegacyCatalogImportException;
use App\Modules\Catalog\Legacy\LegacyCatalogNormalizer;
use App\Modules\Catalog\Legacy\PhpMyAdminJsonTableReader;
use App\Modules\Catalog\Models\CatalogShelf;
use App\Modules\Catalog\Models\CatalogSignature;
use App\Modules\Catalog\Models\CatalogTopic;
use Illuminate\Support\Facades\DB;

/**
 * Prüft Themenliste (`mediaTopicList`) und Regalsignaturen (`mediaSignatures`) des Altsystems gegen den Bestand, ohne etwas zu
 * schreiben: Struktur, Referenzen, Doppelte, Zyklen, Längen, vorhandene und neue Einträge, Konflikte.
 *
 * Themen werden über (Quelle `vdbs-legacy`, Legacy-ID) wiedererkannt, so wie der Altbestandsimport sie angelegt hat.
 */
final class ClassificationImportPlanner
{
    /** Bereichsgruppen und Bereiche der Bibliothek mit ihren Namen. */
    public const AREAS = [
        'I' => ['name' => 'Literatur', 'areas' => ['A' => 'Kinderliteratur', 'B' => 'Jugend- & Erwachsenenliteratur', 'C' => 'Sachliteratur']],
        'II' => ['name' => 'Fachliteratur', 'areas' => ['A' => 'Sprachen & Literatur', 'B' => 'MINT', 'C' => 'Gesellschaftswissenschaften', 'D' => 'Künste', 'E' => 'Prüfungen (BBR, eBBR, MSA, Abitur)', 'F' => 'Lexika & Nachschlagewerke']],
        'III' => ['name' => 'Antidiskriminierungs- & Sensibilisierungsliteratur', 'areas' => ['A' => 'Kinder', 'B' => 'Jugendliche']],
    ];

    public const SIGNATURE_PATTERN = '/^([IVXLCDM]+)\.\s+([A-Za-z]+)\s+(\d+)\s+([A-Za-z])$/';

    private const MAX_ROWS = 5000;

    public function __construct(private PhpMyAdminJsonTableReader $reader) {}

    public function plan(?string $topicsPath, ?string $signaturesPath): ClassificationImportPlan
    {
        $errors = [];
        $warnings = [];
        $conflicts = [];

        $topicRows = $topicsPath !== null ? $this->rows($topicsPath, 'mediaTopicList', 'Themenliste', $errors) : [];
        $signatureRows = $signaturesPath !== null ? $this->rows($signaturesPath, 'mediaSignatures', 'Regalsignaturen', $errors) : [];

        $topics = $this->planTopics($topicRows, $errors, $warnings, $conflicts);
        $signatures = $this->planSignatures($signatureRows, $topics, $errors, $warnings);

        $counts = $this->counts($topics, $signatures, $errors, $warnings, $conflicts);
        $fingerprint = hash('sha256', (string) json_encode([$topics, $signatures], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

        return new ClassificationImportPlan(
            topics: array_values($topics),
            signatures: array_values($signatures),
            counts: $counts,
            errors: $errors,
            warnings: $warnings,
            conflicts: $conflicts,
            fingerprint: $fingerprint,
        );
    }

    /**
     * @param  list<string>  $errors
     * @return list<array<string, mixed>>
     */
    private function rows(string $path, string $table, string $label, array &$errors): array
    {
        try {
            $rows = $this->reader->read($path, $table);
        } catch (LegacyCatalogImportException) {
            // Die Meldung der Leseklasse nennt den Dateipfad auf dem Server; hier gibt es nur den fachlichen Grund.
            $errors[] = $label.': Die Datei ist kein gültiges JSON oder enthält die Tabelle „'.$table.'“ nicht.';

            return [];
        }

        if (count($rows) > self::MAX_ROWS) {
            $errors[] = $label.': Die Datei hat mehr als '.self::MAX_ROWS.' Zeilen.';

            return [];
        }

        return $rows;
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     * @param  list<string>  $errors
     * @param  list<string>  $warnings
     * @param  list<string>  $conflicts
     * @return array<string, array{legacy_id: string, public_key: ?string, name: string, description: ?string, parent: ?string, status: string, changes: list<string>}>
     */
    private function planTopics(array $rows, array &$errors, array &$warnings, array &$conflicts): array
    {
        if ($rows === []) {
            return [];
        }

        $existing = CatalogTopic::query()->where('legacy_source', LegacyCatalogNormalizer::SOURCE)->get()->keyBy('legacy_id');
        $everyTopic = CatalogTopic::query()->get(['id', 'legacy_source', 'legacy_id', 'public_key', 'parent_id', 'name'])->keyBy('id');
        $parsed = [];
        $keys = [];

        foreach ($rows as $index => $row) {
            $position = 'Themenliste, Zeile '.($index + 1);
            $id = $this->text($row['id'] ?? null);

            if ($id === null) {
                $errors[] = $position.': Es fehlt die ID.';

                continue;
            }

            if (mb_strlen($id) > 80) {
                $errors[] = $position.': Die ID ist länger als 80 Zeichen.';

                continue;
            }

            if (isset($parsed[$id])) {
                $errors[] = 'Themenliste: Die ID '.$id.' kommt doppelt vor.';

                continue;
            }

            $name = $this->text($row['topic'] ?? null);

            if ($name === null) {
                $errors[] = $position.' (ID '.$id.'): Es fehlt der Name des Themas.';

                continue;
            }

            if (mb_strlen($name) > 255) {
                $errors[] = $position.' (ID '.$id.'): Der Name ist länger als 255 Zeichen.';

                continue;
            }

            $key = $this->text($row['public_topic_id'] ?? null);

            if ($key !== null && mb_strlen($key) > 80) {
                $errors[] = $position.' (ID '.$id.'): Der öffentliche Schlüssel ist länger als 80 Zeichen.';

                continue;
            }

            $description = $this->text($row['description'] ?? null);

            if ($description !== null && mb_strlen($description) > 5000) {
                $errors[] = $position.' (ID '.$id.'): Die Beschreibung ist länger als 5000 Zeichen.';

                continue;
            }

            $parent = $this->text($row['main_topic'] ?? null);
            $parent = ($parent === null || $parent === '0') ? null : $parent;

            if ($key !== null) {
                if (isset($keys[$key])) {
                    $errors[] = 'Themenliste: Der öffentliche Schlüssel '.$key.' wird von den IDs '.$keys[$key].' und '.$id.' verwendet.';
                } else {
                    $keys[$key] = $id;
                }
            }

            $parsed[$id] = ['legacy_id' => $id, 'public_key' => $key, 'name' => $name, 'description' => $description, 'parent' => $parent, 'status' => 'new', 'changes' => []];
        }

        // Elternreferenzen: in der Datei oder bereits im Bestand (Teilimporte).
        foreach ($parsed as $id => $topic) {
            $parent = $topic['parent'];

            if ($parent === null) {
                continue;
            }

            if ($parent === (string) $id) {
                $errors[] = 'Thema '.$id.' „'.$topic['name'].'“ ist sein eigenes Elternthema.';
            } elseif (! isset($parsed[$parent]) && ! $existing->has($parent)) {
                $errors[] = 'Thema '.$id.' „'.$topic['name'].'“ verweist auf das Elternthema '.$parent.', das weder in der Datei noch im Bestand steht.';
            }
        }

        // Zyklen in der Hierarchie der Datei.
        foreach (array_keys($parsed) as $start) {
            $seen = [];
            $node = (string) $start;

            while ($node !== '' && isset($parsed[$node]) && $parsed[$node]['parent'] !== null) {
                if (isset($seen[$node])) {
                    $errors[] = 'Die Themenhierarchie enthält einen Zyklus (beteiligt: '.implode(', ', array_keys($seen)).').';

                    break 2;
                }

                $seen[$node] = true;
                $node = (string) $parsed[$node]['parent'];
            }
        }

        // Vergleich mit dem Bestand.
        foreach ($parsed as $id => $topic) {
            $model = $existing->get((string) $id);

            // Ein öffentlicher Schlüssel darf nicht bei einem anderen Thema des Bestands liegen.
            if ($topic['public_key'] !== null) {
                $holder = $everyTopic->first(static fn (CatalogTopic $other): bool => $other->public_key === $topic['public_key'] && ($model === null || $other->getKey() !== $model->getKey()));

                if ($holder !== null) {
                    $errors[] = 'Der öffentliche Schlüssel '.$topic['public_key'].' (Thema '.$id.' „'.$topic['name'].'“) ist im Bestand schon beim Thema „'.$holder->name.'“ vergeben.';
                }
            }

            if ($model === null) {
                continue;
            }

            $changes = [];

            if ($model->name !== $topic['name']) {
                $changes[] = 'Name: „'.$model->name.'“ → „'.$topic['name'].'“';
            }

            if (($this->text($model->description)) !== $topic['description']) {
                $changes[] = 'Beschreibung geändert';
            }

            if ($this->text($model->public_key) !== $topic['public_key']) {
                $changes[] = 'Öffentlicher Schlüssel: '.($model->public_key ?? '–').' → '.($topic['public_key'] ?? '–');
            }

            $currentParent = $model->parent_id !== null ? ($everyTopic->get($model->parent_id)?->legacy_id) : null;

            if (($currentParent !== null ? (string) $currentParent : ($model->parent_id !== null ? '#'.$model->parent_id : null)) !== $topic['parent']) {
                $changes[] = 'Elternthema geändert';
            }

            $parsed[$id]['status'] = $changes === [] ? 'same' : 'changed';
            $parsed[$id]['changes'] = $changes;

            if ($changes !== []) {
                $conflicts[] = 'Thema '.$id.' „'.$model->name.'“ weicht vom Bestand ab: '.implode('; ', $changes).'.';
            }
        }

        // Gleiche Namen unter demselben Elternthema sind erlaubt, aber meist ein Versehen.
        $byName = [];

        foreach ($parsed as $id => $topic) {
            $byName[($topic['parent'] ?? '').'|'.mb_strtolower($topic['name'])][] = (string) $id;
        }

        foreach ($byName as $ids) {
            if (count($ids) > 1) {
                $warnings[] = 'Die Themen '.implode(', ', $ids).' heißen gleich „'.$parsed[$ids[0]]['name'].'“ und liegen unter demselben Elternthema.';
            }
        }

        return $parsed;
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     * @param  array<string, array<string, mixed>>  $topics
     * @param  list<string>  $errors
     * @param  list<string>  $warnings
     * @return array<string, array{legacy_id: string, signature: string, topics: list<string>, status: string, shelf: string, shelf_status: string, new_links: int, new_signature_links: int}>
     */
    private function planSignatures(array $rows, array $topics, array &$errors, array &$warnings): array
    {
        if ($rows === []) {
            return [];
        }

        $existingTopics = CatalogTopic::query()->where('legacy_source', LegacyCatalogNormalizer::SOURCE)->get()->keyBy('legacy_id');
        $existingSignatures = CatalogSignature::query()->get();
        $signaturesByValue = $existingSignatures->keyBy('signature');
        $signaturesByLegacy = $existingSignatures->where('legacy_source', LegacyCatalogNormalizer::SOURCE)->keyBy('legacy_id');
        $shelves = CatalogShelf::query()->get();
        $shelvesByCode = $shelves->keyBy('code');
        $shelvesByNormalized = $shelves->keyBy(static fn (CatalogShelf $shelf): string => CatalogShelf::normalizeCode($shelf->code));
        $shelfTopics = DB::table('catalog_shelf_topics')->get()->groupBy('shelf_id');
        $signatureTopics = DB::table('catalog_signature_topics')->get()->groupBy('signature_id');

        $result = [];
        $values = [];
        $normalized = [];
        $unknownAreas = [];

        foreach ($rows as $index => $row) {
            $position = 'Regalsignaturen, Zeile '.($index + 1);
            $id = $this->text($row['id'] ?? null);
            $value = $this->text($row['signature'] ?? null);

            if ($id === null || $value === null) {
                $errors[] = $position.': Es fehlt die ID oder die Signatur.';

                continue;
            }

            $value = (string) preg_replace('/\s+/u', ' ', $value);

            if (isset($result[$id])) {
                $errors[] = 'Regalsignaturen: Die ID '.$id.' kommt doppelt vor.';

                continue;
            }

            if (preg_match(self::SIGNATURE_PATTERN, $value, $m) !== 1) {
                $errors[] = $position.' (ID '.$id.'): Die Signatur „'.$value.'“ hat nicht die Form „I. A 1 a“.';

                continue;
            }

            if (mb_strlen($value) > 40) {
                $errors[] = $position.' (ID '.$id.'): Die Signatur ist länger als 40 Zeichen.';

                continue;
            }

            if (isset($values[$value]) || isset($normalized[CatalogShelf::normalizeCode($value)])) {
                $errors[] = 'Regalsignaturen: Die Signatur „'.$value.'“ kommt doppelt vor (IDs '.($normalized[CatalogShelf::normalizeCode($value)] ?? $values[$value]).' und '.$id.').';

                continue;
            }

            $values[$value] = $id;
            $normalized[CatalogShelf::normalizeCode($value)] = $id;

            $group = strtoupper($m[1]);
            $area = strtoupper($m[2]);

            if (! isset(self::AREAS[$group]['areas'][$area])) {
                $unknownAreas[$group.'. '.$area] = true;
            }

            $topicIds = $this->topicIds($row['topic_ids'] ?? null);

            if ($topicIds === null) {
                $errors[] = $position.' (ID '.$id.'): „topic_ids“ ist keine gültige Liste von Themen-IDs.';

                continue;
            }

            foreach ($topicIds as $topicId) {
                if (! isset($topics[$topicId]) && ! $existingTopics->has($topicId)) {
                    $errors[] = 'Signatur „'.$value.'“ verweist auf das Thema '.$topicId.', das weder in der Themenliste noch im Bestand steht.';
                }
            }

            $signature = $signaturesByValue->get($value);
            $status = $signature === null ? 'new' : 'same';

            if ($signature !== null && $signature->legacy_source === LegacyCatalogNormalizer::SOURCE && $signature->legacy_id !== null && $signature->legacy_id !== $id) {
                $warnings[] = 'Signatur „'.$value.'“ hat im Bestand die Legacy-ID '.$signature->legacy_id.', die Datei nennt '.$id.'; sie wird unverändert weiterverwendet.';
            }

            $byLegacy = $signaturesByLegacy->get($id);

            if ($byLegacy !== null && $byLegacy->signature !== $value) {
                $errors[] = 'Die Legacy-ID '.$id.' gehört im Bestand zur Signatur „'.$byLegacy->signature.'“, die Datei ordnet sie „'.$value.'“ zu.';
            }

            $shelf = $shelvesByCode->get($value) ?? $shelvesByNormalized->get(CatalogShelf::normalizeCode($value));
            $shelfStatus = $shelf === null ? 'new' : 'same';

            if ($shelf !== null && $shelf->code !== $value) {
                $warnings[] = 'Das vorhandene Regalbrett „'.$shelf->code.'“ wird als „'.$value.'“ erkannt und nicht verändert.';
            }

            // Neue Verknüpfungen: Thema steht noch nicht beim Regalbrett beziehungsweise bei der Signatur.
            $shelfLinked = $shelf !== null ? $shelfTopics->get($shelf->getKey(), collect())->pluck('topic_id')->all() : [];
            $signatureLinked = $signature !== null ? $signatureTopics->get($signature->getKey(), collect())->pluck('topic_id')->all() : [];
            $newLinks = 0;
            $newSignatureLinks = 0;

            foreach ($topicIds as $topicId) {
                $topicDbId = $existingTopics->get($topicId)?->getKey();

                if ($topicDbId === null || ! in_array($topicDbId, $shelfLinked, true)) {
                    $newLinks++;
                }

                if ($topicDbId === null || ! in_array($topicDbId, $signatureLinked, true)) {
                    $newSignatureLinks++;
                }
            }

            $result[$id] = [
                'legacy_id' => $id,
                'signature' => $value,
                'topics' => $topicIds,
                'status' => $status,
                'shelf' => $shelf !== null ? $shelf->code : $value,
                'shelf_status' => $shelfStatus,
                'new_links' => $newLinks,
                'new_signature_links' => $newSignatureLinks,
            ];
        }

        foreach (array_keys($unknownAreas) as $area) {
            $warnings[] = 'Der Bereich „'.$area.'“ gehört nicht zur bekannten Gliederung der Bibliothek; er wird trotzdem angelegt.';
        }

        return $result;
    }

    /**
     * @param  array<string, array<string, mixed>>  $topics
     * @param  array<string, array<string, mixed>>  $signatures
     * @param  list<string>  $errors
     * @param  list<string>  $warnings
     * @param  list<string>  $conflicts
     * @return array<string, int>
     */
    private function counts(array $topics, array $signatures, array $errors, array $warnings, array $conflicts): array
    {
        $status = static fn (array $items, string $key, string $value): int => count(array_filter($items, static fn (array $item): bool => $item[$key] === $value));

        return [
            'new_topics' => $status($topics, 'status', 'new'),
            'existing_topics' => $status($topics, 'status', 'same') + $status($topics, 'status', 'changed'),
            'changed_topics' => $status($topics, 'status', 'changed'),
            'conflicts' => count($conflicts),
            'new_signatures' => $status($signatures, 'status', 'new'),
            'existing_signatures' => $status($signatures, 'status', 'same'),
            'new_shelves' => $status($signatures, 'shelf_status', 'new'),
            'existing_shelves' => $status($signatures, 'shelf_status', 'same'),
            'new_assignments' => array_sum(array_column($signatures, 'new_links')),
            'new_signature_assignments' => array_sum(array_column($signatures, 'new_signature_links')),
            'warnings' => count($warnings),
            'errors' => count($errors),
        ];
    }

    private function text(mixed $value): ?string
    {
        if (! is_scalar($value)) {
            return null;
        }

        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    /** @return list<string>|null null, wenn der Wert keine gültige Liste ist */
    private function topicIds(mixed $value): ?array
    {
        if ($value === null || $value === '') {
            return [];
        }

        $data = is_array($value) ? $value : (is_string($value) ? json_decode($value, true) : null);

        if (! is_array($data) || ! array_is_list($data)) {
            return null;
        }

        $result = [];

        foreach ($data as $id) {
            if (! is_scalar($id) || is_bool($id)) {
                return null;
            }

            $id = trim((string) $id);

            if ($id !== '') {
                $result[] = $id;
            }
        }

        return array_values(array_unique($result));
    }
}
