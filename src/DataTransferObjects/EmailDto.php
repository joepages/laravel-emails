<?php

declare(strict_types=1);

namespace Emails\DataTransferObjects;

use Emails\Http\Requests\EmailRequest;

readonly class EmailDto
{
    public function __construct(
        public string $type,
        public string $email,
        public bool $isPrimary = false,
        public bool $isVerified = false,
        public ?string $verifiedAt = null,
        public ?array $metadata = null,
    ) {}

    public static function fromRequest(EmailRequest $request): self
    {
        return self::fromArray($request->validated());
    }

    public static function fromArray(array $data): self
    {
        return new self(
            type: $data['type'] ?? config('emails.default_type', 'personal'),
            email: $data['email'],
            isPrimary: (bool) ($data['is_primary'] ?? false),
            isVerified: (bool) ($data['is_verified'] ?? false),
            verifiedAt: $data['verified_at'] ?? null,
            metadata: $data['metadata'] ?? null,
        );
    }

    public function toArray(): array
    {
        return [
            'type' => $this->type,
            'email' => $this->email,
            'is_primary' => $this->isPrimary,
            'is_verified' => $this->isVerified,
            'verified_at' => $this->verifiedAt,
            'metadata' => $this->metadata,
        ];
    }
}
