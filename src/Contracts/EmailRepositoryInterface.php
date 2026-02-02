<?php

declare(strict_types=1);

namespace Emails\Contracts;

use Emails\Models\Email;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

interface EmailRepositoryInterface
{
    public function find(int $id): ?Email;

    public function create(array $data): Email;

    public function update(Email $email, array $data): Email;

    public function delete(Email $email): bool;

    public function getForParent(Model $parent): Collection;

    public function findForParent(int $emailId, Model $parent): ?Email;

    public function unsetPrimaryForParent(Model $parent): void;

    public function deleteWhereNotIn(Model $parent, array $ids): void;
}
