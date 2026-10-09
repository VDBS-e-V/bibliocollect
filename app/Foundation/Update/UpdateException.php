<?php

declare(strict_types=1);

namespace App\Foundation\Update;

use RuntimeException;

/** Ein Update lässt sich nicht prüfen oder einspielen; die Meldung ist für Menschen geschrieben. */
final class UpdateException extends RuntimeException {}
