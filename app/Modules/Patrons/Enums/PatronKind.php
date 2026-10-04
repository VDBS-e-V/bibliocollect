<?php

declare(strict_types=1);

namespace App\Modules\Patrons\Enums;

enum PatronKind: string
{
    case Student = 'student';
    case Teacher = 'teacher';
    case Employee = 'employee';
}
