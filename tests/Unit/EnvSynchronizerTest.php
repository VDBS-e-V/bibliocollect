<?php

declare(strict_types=1);

use App\Foundation\Environment\EnvSynchronizer;

it('parses keys with the first occurrence and ignores comments', function (): void {
    $entries = (new EnvSynchronizer)->parse("# A=1\nA=1\nexport B = 2\nA=3\n\n");

    expect(array_keys($entries))->toBe(['A', 'B'])->and($entries['A'])->toBe('A=1');
});

it('reads values without quotes and inline comments', function (): void {
    $values = (new EnvSynchronizer)->values("A=\"x y\"\nB='z'\nC=plain # Hinweis\nD=\n");

    expect($values)->toBe(['A' => 'x y', 'B' => 'z', 'C' => 'plain', 'D' => '']);
});

it('returns only missing and extra keys from diff', function (): void {
    expect((new EnvSynchronizer)->diff("A=1\nB=2\n", "B=9\nC=3\n"))->toBe(['missing' => ['A'], 'extra' => ['C']]);
});

it('keeps existing values and appends extras by default', function (): void {
    $result = (new EnvSynchronizer)->synchronize("# Kopf\nA=1\nB=2\n", "B=geheim\nC=3\n");

    expect($result['missing'])->toBe(['A'])->and($result['extra'])->toBe(['C'])->and($result['changed'])->toBe([])
        ->and($result['content'])->toBe("# Kopf\nA=1\nB=geheim\n\n# Weitere Einträge (nicht in der Vorlage)\nC=3\n");
});

it('replaces values with force and drops extras with prune', function (): void {
    $sync = new EnvSynchronizer;

    expect($sync->synchronize("A=1\n", "A=alt\nC=3\n", force: true)['changed'])->toBe(['A']);
    expect($sync->synchronize("A=1\n", "A=1\nC=3\n", prune: true)['content'])->toBe("A=1\n");
});
