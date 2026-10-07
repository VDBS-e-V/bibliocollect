<?php

declare(strict_types=1);

use App\Models\User;
use App\Modules\Circulation\Models\Loan;
use App\Modules\Patrons\Models\Patron;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('provides idempotent demo circulation states and permission boundaries', function (): void {
    $this->seed(DatabaseSeeder::class);

    $student = Patron::query()->where('library_number', '384917')->firstOrFail();
    $teacher = Patron::query()->where('library_number', '156803')->firstOrFail();

    $openLoan = Loan::query()
        ->where('patron_id', $student->getKey())
        ->whereNull('returned_at')
        ->with('copy')
        ->firstOrFail();

    $returnedLoan = Loan::query()
        ->where('patron_id', $teacher->getKey())
        ->whereNotNull('returned_at')
        ->with('copy')
        ->firstOrFail();

    expect(Loan::query()->count())->toBe(2)
        ->and(Loan::query()->whereNull('returned_at')->count())->toBe(1)
        ->and($openLoan->copy->barcode)->toBe('BC-MOMO-001')
        ->and($openLoan->due_on->toDateString())->toBe('2026-10-15')
        ->and($returnedLoan->copy->barcode)->toBe('BC-PRINZ-001');

    $agBasic = User::query()->where('email', 'ag-basic@demo.bibliocollect.test')->firstOrFail();
    $agExtended = User::query()->where('email', 'ag-extended@demo.bibliocollect.test')->firstOrFail();
    $staff = User::query()->where('email', 'staff@demo.bibliocollect.test')->firstOrFail();
    $management = User::query()->where('email', 'management@demo.bibliocollect.test')->firstOrFail();
    $technicalAdmin = User::query()->where('email', 'technik@demo.bibliocollect.test')->firstOrFail();

    expect($agBasic->allowsPermission('circulation.manage'))->toBeTrue()
        ->and($agExtended->allowsPermission('circulation.manage'))->toBeTrue()
        ->and($staff->allowsPermission('circulation.manage'))->toBeTrue()
        ->and($management->allowsPermission('circulation.manage'))->toBeTrue()
        ->and($technicalAdmin->allowsPermission('circulation.manage'))->toBeFalse();

    $this->seed(DatabaseSeeder::class);

    expect(Loan::query()->count())->toBe(2)
        ->and(Loan::query()->whereNull('returned_at')->count())->toBe(1);
});
