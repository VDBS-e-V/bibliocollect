<?php

declare(strict_types=1);

namespace App\Modules\Patrons\Support;

use RuntimeException;

final class PatronLinkCodeHasher
{
    public function normalize(string $code): string
    {
        $normalized = strtoupper(preg_replace('/[^A-Z0-9]/i', '', $code) ?? '');

        return $normalized;
    }

    public function fingerprint(string $code): string
    {
        $key = (string) config('app.key');

        if ($key === '') {
            throw new RuntimeException('APP_KEY is required to fingerprint patron link codes.');
        }

        return hash_hmac('sha256', $this->normalize($code), $key);
    }
}
