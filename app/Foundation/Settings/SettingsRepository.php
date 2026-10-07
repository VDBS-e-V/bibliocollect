<?php

declare(strict_types=1);

namespace App\Foundation\Settings;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * Hält die im Web geänderten Regeln (Tabelle `app_settings`) und legt sie über die Konfiguration. Alle bisherigen
 * `config('circulation.…')`-Zugriffe sehen so automatisch den geänderten Wert, ohne dass der Code etwas davon weiß.
 */
final class SettingsRepository
{
    private const CACHE_KEY = 'app.settings.v1';

    /** @var array<string, mixed> Werte aus den Konfigurationsdateien, bevor etwas überschrieben wurde */
    private array $defaults = [];

    private bool $applied = false;

    public function __construct(private readonly SettingsRegistry $registry) {}

    /** Legt die gespeicherten Werte über die Konfiguration. Ohne Tabelle (vor der Einrichtung) passiert nichts. */
    public function apply(): void
    {
        if ($this->applied) {
            return;
        }

        $this->applied = true;

        foreach ($this->registry->all() as $key => $definition) {
            $this->defaults[$key] = Config::get($key);
        }

        foreach ($this->overrides() as $key => $value) {
            if (isset($this->defaults[$key]) || array_key_exists($key, $this->defaults)) {
                Config::set($key, $value);
            }
        }
    }

    /** Wert aus der Konfigurationsdatei, unabhängig von gespeicherten Änderungen. */
    public function default(string $key): mixed
    {
        $this->apply();

        return $this->defaults[$key] ?? null;
    }

    /** Aktuell geltender Wert. */
    public function current(string $key): mixed
    {
        $this->apply();

        return Config::get($key);
    }

    /**
     * Speichert Werte. Ein Wert, der dem Standard entspricht, entfernt die Änderung.
     *
     * @param  array<string, mixed>  $values  Schlüssel zu Wert
     * @return list<string> Schlüssel, deren Wert sich geändert hat
     */
    public function save(array $values, ?int $userId): array
    {
        $this->apply();
        $changed = [];

        DB::transaction(function () use ($values, $userId, &$changed): void {
            foreach ($values as $key => $value) {
                if (! isset($this->registry->all()[$key])) {
                    continue;
                }

                if ($this->normalize($value) !== $this->normalize(Config::get($key))) {
                    $changed[] = $key;
                }

                if ($this->normalize($value) === $this->normalize($this->defaults[$key] ?? null)) {
                    DB::table('app_settings')->where('key', $key)->delete();

                    continue;
                }

                DB::table('app_settings')->updateOrInsert(['key' => $key], [
                    'value' => json_encode($value, JSON_THROW_ON_ERROR),
                    'updated_by_user_id' => $userId,
                    'updated_at' => now(),
                ]);
            }
        });

        $this->reload($values);

        return $changed;
    }

    /** Verwirft alle Änderungen: Es gelten wieder die Werte aus den Konfigurationsdateien. */
    public function reset(): void
    {
        $this->apply();

        DB::table('app_settings')->delete();

        $this->reload(array_map(fn (mixed $default): mixed => $default, $this->defaults));
    }

    /** @return array<string, mixed> */
    private function overrides(): array
    {
        try {
            /** @var array<string, string> $rows */
            $rows = Cache::rememberForever(self::CACHE_KEY, static fn (): array => DB::table('app_settings')->pluck('value', 'key')->all());
        } catch (Throwable) {
            return [];
        }

        $overrides = [];

        foreach ($rows as $key => $json) {
            $overrides[(string) $key] = json_decode((string) $json, true);
        }

        return $overrides;
    }

    /** @param  array<string, mixed>  $values */
    private function reload(array $values): void
    {
        Cache::forget(self::CACHE_KEY);

        foreach ($values as $key => $value) {
            Config::set($key, $value);
        }

        foreach ($this->overrides() as $key => $value) {
            Config::set($key, $value);
        }
    }

    private function normalize(mixed $value): mixed
    {
        return is_bool($value) ? $value : (is_numeric($value) ? (int) $value : $value);
    }
}
