<?php

declare(strict_types=1);

namespace Emails\Database\Factories;

use Emails\Models\Email;
use Illuminate\Database\Eloquent\Factories\Factory;

class EmailFactory extends Factory
{
    protected $model = Email::class;

    public function definition(): array
    {
        return [
            'type' => $this->faker->randomElement(['personal', 'work', 'billing', 'other']),
            'is_primary' => false,
            'email' => $this->faker->unique()->safeEmail(),
            'is_verified' => false,
            'verified_at' => null,
            'metadata' => null,
        ];
    }

    public function primary(): static
    {
        return $this->state(fn () => ['is_primary' => true]);
    }

    public function personal(): static
    {
        return $this->state(fn () => ['type' => 'personal']);
    }

    public function work(): static
    {
        return $this->state(fn () => ['type' => 'work']);
    }

    public function billing(): static
    {
        return $this->state(fn () => ['type' => 'billing']);
    }

    public function other(): static
    {
        return $this->state(fn () => ['type' => 'other']);
    }

    public function verified(): static
    {
        return $this->state(fn () => [
            'is_verified' => true,
            'verified_at' => now(),
        ]);
    }
}
