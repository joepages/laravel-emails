<?php

declare(strict_types=1);

namespace Emails\Tests\Helpers;

use Emails\Concerns\HasEmails;
use Illuminate\Database\Eloquent\Model;

/**
 * A dummy model for testing the HasEmails trait.
 */
class TestModel extends Model
{
    use HasEmails;

    protected $table = 'test_models';

    protected $guarded = [];
}
