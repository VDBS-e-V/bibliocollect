<?php

declare(strict_types=1);

namespace App\Modules\Patrons\Actions;

use App\Modules\Patrons\DTOs\PatronUpdateData;
use App\Modules\Patrons\Enums\PatronKind;
use App\Modules\Patrons\Models\Patron;
use Illuminate\Support\Facades\DB;

final class UpdatePatronAction
{
    public function execute(Patron $patron, PatronUpdateData $data): Patron
    {
        return DB::transaction(function () use ($patron, $data): Patron {
            $lockedPatron = Patron::query()
                ->whereKey($patron->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            $lockedPatron->forceFill([
                'library_number' => $data->libraryNumber,
                'first_name' => $data->firstName,
                'last_name' => $data->lastName,
                'birth_date' => $data->birthDate,
                'email' => $data->email,
                'school_class_id' => $lockedPatron->kind === PatronKind::Student ? $data->schoolClassId : null,
                'leaving_on' => $data->leavingOn,
            ])->save();

            return $lockedPatron->load('schoolClass');
        });
    }
}
