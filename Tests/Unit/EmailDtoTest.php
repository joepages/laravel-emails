<?php

declare(strict_types=1);

namespace Emails\Tests\Unit;

use Emails\DataTransferObjects\EmailDto;
use Emails\Tests\UnitTestCase;

class EmailDtoTest extends UnitTestCase
{
    public function test_it_creates_dto_from_array(): void
    {
        $data = [
            'type' => 'work',
            'email' => 'john@example.com',
            'is_primary' => true,
            'is_verified' => true,
            'verified_at' => '2025-01-15 10:00:00',
            'metadata' => ['label' => 'Main work email'],
        ];

        $dto = EmailDto::fromArray($data);

        $this->assertEquals('work', $dto->type);
        $this->assertEquals('john@example.com', $dto->email);
        $this->assertTrue($dto->isPrimary);
        $this->assertTrue($dto->isVerified);
        $this->assertEquals('2025-01-15 10:00:00', $dto->verifiedAt);
        $this->assertEquals(['label' => 'Main work email'], $dto->metadata);
    }

    public function test_it_converts_to_array(): void
    {
        $dto = new EmailDto(
            type: 'personal',
            email: 'jane@example.com',
        );

        $array = $dto->toArray();

        $this->assertEquals('personal', $array['type']);
        $this->assertEquals('jane@example.com', $array['email']);
        $this->assertFalse($array['is_primary']);
        $this->assertFalse($array['is_verified']);
        $this->assertNull($array['verified_at']);
        $this->assertNull($array['metadata']);
    }

    public function test_it_uses_default_type_from_config(): void
    {
        config(['emails.default_type' => 'billing']);

        $data = [
            'email' => 'billing@example.com',
        ];

        $dto = EmailDto::fromArray($data);

        $this->assertEquals('billing', $dto->type);
    }

    public function test_is_primary_defaults_to_false(): void
    {
        $data = [
            'type' => 'personal',
            'email' => 'test@example.com',
        ];

        $dto = EmailDto::fromArray($data);

        $this->assertFalse($dto->isPrimary);
    }

    public function test_is_verified_defaults_to_false(): void
    {
        $data = [
            'type' => 'personal',
            'email' => 'test@example.com',
        ];

        $dto = EmailDto::fromArray($data);

        $this->assertFalse($dto->isVerified);
        $this->assertNull($dto->verifiedAt);
    }

    public function test_it_is_readonly(): void
    {
        $dto = new EmailDto(
            type: 'personal',
            email: 'test@example.com',
        );

        $this->expectException(\Error::class);
        $dto->type = 'work'; // @phpstan-ignore-line
    }
}
