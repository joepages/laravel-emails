<?php

declare(strict_types=1);

namespace Emails\Concerns;

use Emails\Contracts\EmailServiceInterface;
use Emails\DataTransferObjects\EmailDto;
use Emails\Http\Requests\EmailRequest;
use Emails\Http\Resources\EmailCollection;
use Emails\Http\Resources\EmailResource;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Controller trait for managing emails on a parent model.
 *
 * Provides:
 * - attachEmail(): called by BaseApiController::attachRelatedData() for bulk sync
 * - storeEmail(), updateEmail(), deleteEmail(), listEmails(): standalone CRUD endpoints
 *
 * The consuming controller MUST define:
 * - $modelClass (string): The parent model class
 * - $serviceInterface: The parent model's service interface
 */
trait ManagesEmails
{
    /**
     * Called by BaseApiController::attachRelatedData() during store/update.
     * Supports bulk sync: if 'emails' key exists in request, syncs all emails.
     */
    protected function attachEmail(Request $request, Model $model): void
    {
        if (! $request->has('emails')) {
            return;
        }

        $emailsData = $request->input('emails', []);

        if (empty($emailsData)) {
            return;
        }

        $emailService = app(EmailServiceInterface::class);
        $emailService->sync($model, $emailsData);
    }

    /**
     * List all emails for a parent model.
     */
    public function listEmails(int $parentId): JsonResource
    {
        $parent = $this->resolveParentModel($parentId);

        $this->authorize('view', $parent);

        $emailService = app(EmailServiceInterface::class);
        $emails = $emailService->getForParent($parent);

        return new EmailCollection($emails);
    }

    /**
     * Store a new email for a parent model.
     */
    public function storeEmail(EmailRequest $request, int $parentId): JsonResource
    {
        $parent = $this->resolveParentModel($parentId);

        $this->authorize('update', $parent);

        $dto = EmailDto::fromRequest($request);
        $emailService = app(EmailServiceInterface::class);
        $email = $emailService->store($parent, $dto);

        return (new EmailResource($email))
            ->response()
            ->setStatusCode(201)
            ->original;
    }

    /**
     * Update an existing email for a parent model.
     */
    public function updateEmail(EmailRequest $request, int $parentId, int $emailId): JsonResource
    {
        $parent = $this->resolveParentModel($parentId);

        $this->authorize('update', $parent);

        $emailService = app(EmailServiceInterface::class);
        $email = $emailService->findForParent($emailId, $parent);

        if (! $email) {
            abort(404, 'Email not found.');
        }

        $dto = EmailDto::fromRequest($request);
        $email = $emailService->update($email, $dto);

        return new EmailResource($email);
    }

    /**
     * Delete an email for a parent model.
     */
    public function deleteEmail(int $parentId, int $emailId): JsonResponse
    {
        $parent = $this->resolveParentModel($parentId);

        $this->authorize('update', $parent);

        $emailService = app(EmailServiceInterface::class);
        $email = $emailService->findForParent($emailId, $parent);

        if (! $email) {
            abort(404, 'Email not found.');
        }

        $emailService->delete($email);

        return response()->json(['message' => 'Email deleted successfully.'], 200);
    }

    /**
     * Resolve the parent model by ID.
     */
    protected function resolveParentModel(int $parentId): Model
    {
        return $this->service->getById($parentId);
    }
}
