<?php

declare(strict_types=1);
use App\Modules\Catalog\Models\Copy;
use App\Modules\Catalog\Models\Edition;
use Illuminate\Support\Str;
use Tests\TestCase;

pest()->extend(TestCase::class)
    ->in('Feature', 'Architecture');

/** Gibt die Ausgabe zurück und legt ein vorhandenes Exemplar an: Der öffentliche Katalog zeigt nur Titel mit Exemplaren. */
function catalogTestWithCopy(Edition $edition): Edition
{
    Copy::query()->create(['edition_id' => $edition->getKey(), 'barcode' => 'T'.strtoupper(Str::random(12)), 'status' => 'active']);

    return $edition;
}
