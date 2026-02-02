<?php

declare(strict_types=1);

namespace Emails\Repositories;

use Emails\Contracts\EmailRepositoryInterface;
use Emails\Models\Email;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class EmailRepository implements EmailRepositoryInterface
{
    public function __construct(
        protected Email $model,
    ) {}

    public function find(int $id): ?Email
    {
        return $this->model->find($id);
    }

    public function create(array $data): Email
    {
        return $this->model->create($data);
    }

    public function update(Email $email, array $data): Email
    {
        $email->update($data);

        return $email->fresh();
    }

    public function delete(Email $email): bool
    {
        return (bool) $email->delete();
    }

    public function getForParent(Model $parent): Collection
    {
        return $this->model
            ->where('emailable_type', $parent->getMorphClass())
            ->where('emailable_id', $parent->getKey())
            ->orderByDesc('is_primary')
            ->orderBy('type')
            ->get();
    }

    public function findForParent(int $emailId, Model $parent): ?Email
    {
        return $this->model
            ->where('id', $emailId)
            ->where('emailable_type', $parent->getMorphClass())
            ->where('emailable_id', $parent->getKey())
            ->first();
    }

    public function unsetPrimaryForParent(Model $parent): void
    {
        $this->model
            ->where('emailable_type', $parent->getMorphClass())
            ->where('emailable_id', $parent->getKey())
            ->update(['is_primary' => false]);
    }

    public function deleteWhereNotIn(Model $parent, array $ids): void
    {
        $this->model
            ->where('emailable_type', $parent->getMorphClass())
            ->where('emailable_id', $parent->getKey())
            ->whereNotIn('id', $ids)
            ->delete();
    }
}
