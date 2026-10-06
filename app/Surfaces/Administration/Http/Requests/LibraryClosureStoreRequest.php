<?php

declare(strict_types=1);

namespace App\Surfaces\Administration\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class LibraryClosureStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'from' => ['required', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
            'reason' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function from(): string
    {
        return (string) $this->validated('from');
    }

    /** Ohne Enddatum gilt ein einzelner Tag. */
    public function to(): string
    {
        $to = $this->validated('to');

        return is_string($to) && $to !== '' ? $to : $this->from();
    }

    public function reason(): ?string
    {
        $reason = $this->validated('reason');

        return is_string($reason) ? $reason : null;
    }
}
