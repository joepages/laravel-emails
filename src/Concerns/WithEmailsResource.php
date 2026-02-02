<?php

declare(strict_types=1);

namespace Emails\Concerns;

use Emails\Http\Resources\EmailResource;

/**
 * Trait for API Resources to include emails in the response.
 *
 * Usage:
 *   class FacilityResource extends BaseResource {
 *       use WithEmailsResource;
 *
 *       public function toArray($request): array {
 *           return array_merge([
 *               'id' => $this->id,
 *               'name' => $this->name,
 *           ], $this->emailsResource());
 *       }
 *   }
 */
trait WithEmailsResource
{
    protected function emailsResource(): array
    {
        return [
            'emails' => EmailResource::collection($this->whenLoaded('emails')),
            'primary_email' => $this->whenLoaded('primaryEmail', function () {
                return $this->primaryEmail ? new EmailResource($this->primaryEmail) : null;
            }),
        ];
    }
}
