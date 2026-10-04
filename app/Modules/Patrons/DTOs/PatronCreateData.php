<?php

declare(strict_types=1);

namespace App\Modules\Patrons\DTOs;

use App\Modules\Patrons\Enums\PatronKind;

final readonly class PatronCreateData
{
    public function __construct(
        public string $libraryNumber,
        public PatronKind $kind,
        public string $firstName,
        public string $lastName,
        public string $birthDate,
        public ?string $email,
        public ?string $schoolClassId,
        public ?string $leavingOn,
    ) {}
}
