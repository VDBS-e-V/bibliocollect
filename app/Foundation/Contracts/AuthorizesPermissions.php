<?php

declare(strict_types=1);

namespace App\Foundation\Contracts;

interface AuthorizesPermissions
{
    public function allowsPermission(string $permission): bool;
}
