<?php

declare(strict_types=1);

namespace Emails\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Carbon;

class Email extends Model
{
    use HasFactory;

    protected $table = 'emails';

    protected $fillable = [
        'emailable_type',
        'emailable_id',
        'type',
        'is_primary',
        'email',
        'is_verified',
        'verified_at',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'is_primary' => 'boolean',
            'is_verified' => 'boolean',
            'verified_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    // =========================================================================
    // Relationships
    // =========================================================================

    public function emailable(): MorphTo
    {
        return $this->morphTo();
    }

    // =========================================================================
    // Scopes
    // =========================================================================

    public function scopePrimary(Builder $query): Builder
    {
        return $query->where('is_primary', true);
    }

    public function scopeOfType(Builder $query, string $type): Builder
    {
        return $query->where('type', $type);
    }

    public function scopeForModel(Builder $query, Model $model): Builder
    {
        return $query->where('emailable_type', $model->getMorphClass())
            ->where('emailable_id', $model->getKey());
    }

    public function scopeVerified(Builder $query): Builder
    {
        return $query->where('is_verified', true);
    }

    // =========================================================================
    // Helpers
    // =========================================================================

    /**
     * Mark this email as primary and unmark all other emails for the same parent.
     */
    public function markAsPrimary(): bool
    {
        static::where('emailable_type', $this->emailable_type)
            ->where('emailable_id', $this->emailable_id)
            ->where('id', '!=', $this->id)
            ->update(['is_primary' => false]);

        $this->is_primary = true;

        return $this->save();
    }

    /**
     * Get the domain part of the email address.
     */
    public function getDomainAttribute(): ?string
    {
        if (! $this->email) {
            return null;
        }

        $parts = explode('@', $this->email);

        return $parts[1] ?? null;
    }

    /**
     * Mark this email as verified.
     */
    public function markAsVerified(): bool
    {
        $this->is_verified = true;
        $this->verified_at = Carbon::now();

        return $this->save();
    }

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory()
    {
        return \Emails\Database\Factories\EmailFactory::new();
    }
}
