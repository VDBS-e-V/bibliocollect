<?php

declare(strict_types=1);

namespace App\Modules\Patrons\Exceptions;

use RuntimeException;

final class PatronLinkCodeCannotBeIssued extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('Für dieses Ausleihkonto kann derzeit kein Onlinekonto-Code ausgegeben werden.');
    }
}
