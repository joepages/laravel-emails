<?php

declare(strict_types=1);

namespace Emails\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class EmailRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $typeRule = config('emails.allow_custom_types', true)
            ? 'string|max:50'
            : 'string|in:' . implode(',', config('emails.types', []));

        return [
            'email' => ['required', 'email', 'max:255'],
            'type' => ['sometimes', $typeRule],
            'is_primary' => ['sometimes', 'boolean'],
            'is_verified' => ['sometimes', 'boolean'],
            'verified_at' => ['nullable', 'date'],
            'metadata' => ['nullable', 'array'],
        ];
    }
}
