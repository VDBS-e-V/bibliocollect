<?php

declare(strict_types=1);

use App\Models\User;
use App\Modules\Catalog\Enums\CopyStatus;
use App\Modules\Catalog\Models\Contributor;
use App\Modules\Catalog\Models\Copy;
use App\Modules\Catalog\Models\Edition;
use App\Modules\Catalog\Models\Title;
use App\Modules\Catalog\Models\TitleContribution;
use App\Modules\Patrons\Enums\PatronStatus;
use App\Modules\Patrons\Models\Patron;
use App\Modules\Patrons\Models\PatronAccountLinkToken;
use App\Modules\Patrons\Models\PatronBlockEvent;
use App\Modules\Patrons\Models\PatronStatusEvent;
use App\Modules\Patrons\Support\PatronLinkCodeHasher;
use App\Modules\School\Models\LibraryClosure;
use App\Modules\School\Models\LibraryOpeningHour;
use App\Modules\School\Models\SchoolClass;
use App\Modules\School\Models\SchoolYear;
use Database\Seeders\DemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('provides comprehensive idempotent demo data for the implemented domains', function (): void {
    $this->seed(DemoSeeder::class);

    expect(SchoolYear::query()->count())->toBe(3)
        ->and(SchoolClass::query()->count())->toBe(18)
        ->and(LibraryOpeningHour::query()->count())->toBe(7)
        ->and(LibraryClosure::query()->count())->toBe(4)
        ->and(Patron::query()->count())->toBe(9)
        ->and(User::query()->where('email', 'like', '%@demo.bibliocollect.test')->count())->toBe(8);

    $management = User::query()->where('email', 'management@demo.bibliocollect.test')->firstOrFail();
    $extendedAg = User::query()->where('email', 'ag-extended@demo.bibliocollect.test')->firstOrFail();
    $technicalAdmin = User::query()->where('email', 'technik@demo.bibliocollect.test')->firstOrFail();

    expect($management->roleKeys())->toBe(['management'])
        ->and($extendedAg->roleKeys())->toContain('student', 'student_ag_extended')
        ->and($extendedAg->allowsPermission('catalog.manage'))->toBeTrue()
        ->and($technicalAdmin->roleKeys())->toBe(['technical_admin'])
        ->and($technicalAdmin->allowsPermission('catalog.manage'))->toBeFalse();

    $blockedPatron = Patron::query()->where('library_number', 'S-10003')->firstOrFail();
    $historyPatron = Patron::query()->where('library_number', 'S-10005')->firstOrFail();
    $departedPatron = Patron::query()->where('library_number', 'S-10006')->firstOrFail();
    $departedUser = User::query()->where('email', 'departed@demo.bibliocollect.test')->firstOrFail();

    expect($blockedPatron->blocked_at)->not->toBeNull()
        ->and(PatronBlockEvent::query()->where('patron_id', $blockedPatron->getKey())->count())->toBe(1)
        ->and($historyPatron->blocked_at)->toBeNull()
        ->and(PatronBlockEvent::query()->where('patron_id', $historyPatron->getKey())->count())->toBe(2)
        ->and($departedPatron->status)->toBe(PatronStatus::Departed)
        ->and($departedPatron->school_class_id)->toBeNull()
        ->and($departedUser->disabled_at)->not->toBeNull()
        ->and(PatronStatusEvent::query()->where('patron_id', $departedPatron->getKey())->count())->toBe(1);

    $linkPatron = Patron::query()->where('library_number', 'S-10004')->firstOrFail();
    $openToken = PatronAccountLinkToken::query()
        ->where('patron_id', $linkPatron->getKey())
        ->whereNull('used_at')
        ->whereNull('revoked_at')
        ->firstOrFail();

    expect($openToken->fingerprint)->toBe(app(PatronLinkCodeHasher::class)->fingerprint(DemoSeeder::DEMO_LINK_CODE))
        ->and($openToken->fingerprint)->not->toContain(DemoSeeder::DEMO_LINK_CODE)
        ->and(PatronAccountLinkToken::query()->count())->toBe(3);

    expect(Title::query()->count())->toBe(7)
        ->and(Edition::query()->count())->toBe(8)
        ->and(Copy::query()->count())->toBe(14)
        ->and(Contributor::query()->count())->toBe(8)
        ->and(TitleContribution::query()->count())->toBe(9)
        ->and(Copy::query()->where('status', CopyStatus::Damaged->value)->count())->toBe(1)
        ->and(Copy::query()->where('status', CopyStatus::Lost->value)->count())->toBe(1)
        ->and(Copy::query()->where('status', CopyStatus::Withdrawn->value)->count())->toBe(1);

    $damagedCopy = Copy::query()->where('barcode', 'BC-MOMO-002')->firstOrFail();
    $lostCopy = Copy::query()->where('barcode', 'BC-UNEND-002')->firstOrFail();
    $withdrawnCopy = Copy::query()->where('barcode', 'BC-KRAB-002')->firstOrFail();

    expect($damagedCopy->status)->toBe(CopyStatus::Damaged)
        ->and($damagedCopy->shelf_location)->toBe('J 5 ENDE')
        ->and($lostCopy->status)->toBe(CopyStatus::Lost)
        ->and($withdrawnCopy->status)->toBe(CopyStatus::Withdrawn)
        ->and($withdrawnCopy->shelf_location)->toBe('MAG PREU');

    $michaelEnde = Contributor::query()->where('display_name', 'Michael Ende')->firstOrFail();

    expect($michaelEnde->contributions()->count())->toBe(2);

    $this->seed(DemoSeeder::class);

    expect(SchoolYear::query()->count())->toBe(3)
        ->and(SchoolClass::query()->count())->toBe(18)
        ->and(Patron::query()->count())->toBe(9)
        ->and(User::query()->where('email', 'like', '%@demo.bibliocollect.test')->count())->toBe(8)
        ->and(PatronBlockEvent::query()->count())->toBe(3)
        ->and(PatronStatusEvent::query()->count())->toBe(1)
        ->and(PatronAccountLinkToken::query()->count())->toBe(3)
        ->and(Title::query()->count())->toBe(7)
        ->and(Edition::query()->count())->toBe(8)
        ->and(Copy::query()->count())->toBe(14)
        ->and(Contributor::query()->count())->toBe(8)
        ->and(TitleContribution::query()->count())->toBe(9);
});
