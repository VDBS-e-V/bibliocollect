<?php

declare(strict_types=1);

namespace App\Modules\Identity\Exceptions;

use RuntimeException;

final class PatronAlreadyLinked extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('Für dieses Ausleihkonto besteht bereits ein Onlinekonto.');
    }
}
