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
        $typeRules = config('emails.allow_custom_types', true)
            ? ['string', 'max:50']
            : ['string', 'in:' . implode(',', config('emails.types', []))];

        return [
            'email' => ['required', 'email', 'max:255'],
            'type' => ['sometimes', ...$typeRules],
            'is_primary' => ['sometimes', 'boolean'],
            'is_verified' => ['sometimes', 'boolean'],
            'verified_at' => ['nullable', 'date'],
            'metadata' => ['nullable', 'array'],
        ];
    }

    /**
     * Embeddable rules for parent requests.
     *
     * Usage: ...EmailRequest::embeddedRules() in a parent FormRequest::rules()
     */
    public static function embeddedRules(string $prefix = 'emails'): array
    {
        $typeRules = config('emails.allow_custom_types', true)
            ? ['string', 'max:50']
            : ['string', 'in:' . implode(',', config('emails.types', []))];

        return [
            $prefix => ['sometimes', 'array'],
            "{$prefix}.*.id" => ['sometimes', 'integer', 'exists:emails,id'],
            "{$prefix}.*.email" => ['required', 'email', 'max:255'],
            "{$prefix}.*.type" => ['sometimes', ...$typeRules],
            "{$prefix}.*.is_primary" => ['sometimes', 'boolean'],
            "{$prefix}.*.is_verified" => ['sometimes', 'boolean'],
            "{$prefix}.*.verified_at" => ['nullable', 'date'],
            "{$prefix}.*.metadata" => ['nullable', 'array'],
        ];
    }
}
