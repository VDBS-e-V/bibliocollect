<?php

declare(strict_types=1);

namespace App\Modules\Circulation\Exceptions;

use RuntimeException;

final class BookmarkLimitReached extends RuntimeException {}
