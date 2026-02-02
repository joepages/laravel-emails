<?php

declare(strict_types=1);

namespace Emails\Contracts;

use Emails\DataTransferObjects\EmailDto;
use Emails\Models\Email;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

interface EmailServiceInterface
{
    public function store(Model $parent, EmailDto $dto): Email;

    public function update(Email $email, EmailDto $dto): Email;

    public function delete(Email $email): bool;

    public function getForParent(Model $parent): Collection;

    public function findForParent(int $emailId, Model $parent): ?Email;

    /**
     * Sync emails for a parent model.
     * Creates new, updates existing (matched by id), deletes missing.
     *
     * @param  array<int, array>  $emailsData
     */
    public function sync(Model $parent, array $emailsData): Collection;
}
