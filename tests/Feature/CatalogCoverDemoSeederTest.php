<?php

declare(strict_types=1);

use App\Modules\Catalog\Models\Edition;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('seeds a visible pending cover state without performing external downloads', function (): void {
    $this->seed(DatabaseSeeder::class);

    $edition = Edition::query()->where('isbn', '9780544336261')->firstOrFail();

    expect($edition->getAttribute('cover_status'))->toBe('pending')
        ->and($edition->getAttribute('cover_path'))->toBeNull()
        ->and($edition->getAttribute('cover_fetched_at'))->toBeNull();

    $this->seed(DatabaseSeeder::class);

    expect(Edition::query()->where('isbn', '9780544336261')->firstOrFail()->getAttribute('cover_status'))->toBe('pending');
});
