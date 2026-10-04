<?php

declare(strict_types=1);

namespace App\Modules\Patrons\DTOs;

final readonly class PatronUpdateData
{
    public function __construct(
        public string $libraryNumber,
        public string $firstName,
        public string $lastName,
        public string $birthDate,
        public ?string $email,
        public ?string $schoolClassId,
        public ?string $leavingOn,
    ) {}
}
