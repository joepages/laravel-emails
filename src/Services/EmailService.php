<?php

declare(strict_types=1);

namespace Emails\Services;

use Emails\Contracts\EmailRepositoryInterface;
use Emails\Contracts\EmailServiceInterface;
use Emails\DataTransferObjects\EmailDto;
use Emails\Models\Email;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class EmailService implements EmailServiceInterface
{
    public function __construct(
        protected EmailRepositoryInterface $repository,
    ) {}

    public function store(Model $parent, EmailDto $dto): Email
    {
        $data = array_merge($dto->toArray(), [
            'emailable_type' => $parent->getMorphClass(),
            'emailable_id' => $parent->getKey(),
        ]);

        if ($dto->isPrimary) {
            $this->repository->unsetPrimaryForParent($parent);
        }

        return $this->repository->create($data);
    }

    public function update(Email $email, EmailDto $dto): Email
    {
        $data = $dto->toArray();

        if ($dto->isPrimary && ! $email->is_primary) {
            $parent = $email->emailable;
            $this->repository->unsetPrimaryForParent($parent);
        }

        return $this->repository->update($email, $data);
    }

    public function delete(Email $email): bool
    {
        return $this->repository->delete($email);
    }

    public function getForParent(Model $parent): Collection
    {
        return $this->repository->getForParent($parent);
    }

    public function findForParent(int $emailId, Model $parent): ?Email
    {
        return $this->repository->findForParent($emailId, $parent);
    }

    /**
     * Sync emails for a parent model.
     * Creates new entries, updates existing (matched by id), deletes missing.
     */
    public function sync(Model $parent, array $emailsData): Collection
    {
        $keptIds = [];

        foreach ($emailsData as $emailData) {
            $dto = EmailDto::fromArray($emailData);

            if (isset($emailData['id'])) {
                // Update existing
                $email = $this->findForParent((int) $emailData['id'], $parent);
                if ($email) {
                    $this->update($email, $dto);
                    $keptIds[] = $email->id;

                    continue;
                }
            }

            // Create new
            $email = $this->store($parent, $dto);
            $keptIds[] = $email->id;
        }

        // Delete emails not in the payload
        if (! empty($keptIds)) {
            $this->repository->deleteWhereNotIn($parent, $keptIds);
        }

        return $this->getForParent($parent);
    }
}
