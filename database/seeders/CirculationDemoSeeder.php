<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\User;
use App\Modules\Catalog\Models\Copy;
use App\Modules\Circulation\Models\Loan;
use App\Modules\Patrons\Models\Patron;
use Illuminate\Database\Seeder;
use RuntimeException;

final class CirculationDemoSeeder extends Seeder
{
    public function run(): void
    {
        if (app()->environment('production')) {
            throw new RuntimeException('CirculationDemoSeeder darf nicht in production ausgeführt werden.');
        }

        $staff = User::query()
            ->where('email', 'staff@demo.bibliocollect.test')
            ->firstOrFail();

        $student = Patron::query()
            ->where('library_number', '384917')
            ->firstOrFail();

        $teacher = Patron::query()
            ->where('library_number', '156803')
            ->firstOrFail();

        $openCopy = Copy::query()
            ->where('barcode', 'BC-MOMO-001')
            ->firstOrFail();

        $returnedCopy = Copy::query()
            ->where('barcode', 'BC-PRINZ-001')
            ->firstOrFail();

        Loan::query()->updateOrCreate(
            [
                'patron_id' => $student->getKey(),
                'copy_id' => $openCopy->getKey(),
                'checked_out_at' => '2026-10-01 10:00:00',
            ],
            [
                'due_on' => '2026-10-15',
                'returned_at' => null,
                'checked_out_by_user_id' => $staff->getKey(),
                'returned_by_user_id' => null,
            ],
        );

        Loan::query()->updateOrCreate(
            [
                'patron_id' => $teacher->getKey(),
                'copy_id' => $returnedCopy->getKey(),
                'checked_out_at' => '2026-09-10 10:00:00',
            ],
            [
                'due_on' => '2026-09-24',
                'returned_at' => '2026-09-22 11:15:00',
                'checked_out_by_user_id' => $staff->getKey(),
                'returned_by_user_id' => $staff->getKey(),
            ],
        );
    }
}
