<?php

declare(strict_types=1);

namespace Emails\Concerns;

use Emails\Models\Email;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;

/**
 * Trait to add to any Eloquent model that can have emails.
 *
 * Usage:
 *   use Emails\Concerns\HasEmails;
 *
 *   class Facility extends Model {
 *       use HasEmails;
 *   }
 */
trait HasEmails
{
    public function emails(): MorphMany
    {
        return $this->morphMany(Email::class, 'emailable');
    }

    public function primaryEmail(): MorphOne
    {
        return $this->morphOne(Email::class, 'emailable')
            ->where('is_primary', true);
    }

    public function emailsOfType(string $type): MorphMany
    {
        return $this->emails()->where('type', $type);
    }
}
