<?php

declare(strict_types=1);

namespace App\Modules\Patrons\Actions;

use App\Modules\Patrons\DTOs\PatronCreateData;
use App\Modules\Patrons\Enums\PatronKind;
use App\Modules\Patrons\Enums\PatronStatus;
use App\Modules\Patrons\Models\Patron;
use App\Modules\Patrons\Support\PatronLibraryNumber;

final class CreatePatronAction
{
    public function execute(PatronCreateData $data): Patron
    {
        /** @var Patron $patron */
        $patron = Patron::query()->create([
            'library_number' => trim($data->libraryNumber) !== '' ? trim($data->libraryNumber) : PatronLibraryNumber::generate(),
            'kind' => $data->kind,
            'status' => PatronStatus::Active,
            'first_name' => $data->firstName,
            'last_name' => $data->lastName,
            'birth_date' => $data->birthDate,
            'email' => $data->email,
            'school_class_id' => $data->kind === PatronKind::Student ? $data->schoolClassId : null,
            'leaving_on' => $data->leavingOn,
        ]);

        return $patron->load('schoolClass');
    }
}
