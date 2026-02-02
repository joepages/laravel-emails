<?php

declare(strict_types=1);

namespace Emails\Tests\Feature;

use Emails\Contracts\EmailServiceInterface;
use Emails\DataTransferObjects\EmailDto;
use Emails\Models\Email;
use Emails\Tests\Helpers\TestModel;
use Emails\Tests\TestCase;

class EmailCrudTest extends TestCase
{
    private EmailServiceInterface $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(EmailServiceInterface::class);
    }

    public function test_it_creates_an_email_for_a_model(): void
    {
        $parent = TestModel::create(['name' => 'Test Parent']);

        $dto = new EmailDto(
            type: 'personal',
            email: 'john@example.com',
        );

        $email = $this->service->store($parent, $dto);

        $this->assertInstanceOf(Email::class, $email);
        $this->assertEquals('john@example.com', $email->email);
        $this->assertEquals('personal', $email->type);
        $this->assertEquals($parent->getMorphClass(), $email->emailable_type);
        $this->assertEquals($parent->id, $email->emailable_id);
    }

    public function test_it_updates_an_email(): void
    {
        $parent = TestModel::create(['name' => 'Test Parent']);

        $dto = new EmailDto(
            type: 'personal',
            email: 'john@example.com',
        );

        $email = $this->service->store($parent, $dto);

        $updateDto = new EmailDto(
            type: 'work',
            email: 'john@company.com',
        );

        $updated = $this->service->update($email, $updateDto);

        $this->assertEquals('john@company.com', $updated->email);
        $this->assertEquals('work', $updated->type);
    }

    public function test_it_deletes_an_email(): void
    {
        $parent = TestModel::create(['name' => 'Test Parent']);

        $dto = new EmailDto(
            type: 'personal',
            email: 'john@example.com',
        );

        $email = $this->service->store($parent, $dto);
        $emailId = $email->id;

        $result = $this->service->delete($email);

        $this->assertTrue($result);
        $this->assertNull(Email::find($emailId));
    }

    public function test_it_gets_all_emails_for_a_parent(): void
    {
        $parent = TestModel::create(['name' => 'Test Parent']);

        $this->service->store($parent, new EmailDto(
            type: 'personal',
            email: 'john@example.com',
        ));

        $this->service->store($parent, new EmailDto(
            type: 'work',
            email: 'john@company.com',
        ));

        $emails = $this->service->getForParent($parent);

        $this->assertCount(2, $emails);
    }

    public function test_setting_primary_unsets_other_primaries(): void
    {
        $parent = TestModel::create(['name' => 'Test Parent']);

        $email1 = $this->service->store($parent, new EmailDto(
            type: 'personal',
            email: 'john@example.com',
            isPrimary: true,
        ));

        $this->assertTrue($email1->is_primary);

        $email2 = $this->service->store($parent, new EmailDto(
            type: 'work',
            email: 'john@company.com',
            isPrimary: true,
        ));

        $this->assertTrue($email2->is_primary);
        $this->assertFalse($email1->fresh()->is_primary);
    }

    public function test_it_syncs_emails(): void
    {
        $parent = TestModel::create(['name' => 'Test Parent']);

        // Create initial emails
        $email1 = $this->service->store($parent, new EmailDto(
            type: 'personal',
            email: 'john@example.com',
        ));

        $email2 = $this->service->store($parent, new EmailDto(
            type: 'work',
            email: 'john@company.com',
        ));

        // Sync: update email1, drop email2, add new email3
        $result = $this->service->sync($parent, [
            [
                'id' => $email1->id,
                'type' => 'personal',
                'email' => 'john.updated@example.com',
            ],
            [
                'type' => 'billing',
                'email' => 'billing@example.com',
            ],
        ]);

        $this->assertCount(2, $result);
        $this->assertNull(Email::find($email2->id));
        $this->assertEquals('john.updated@example.com', $email1->fresh()->email);
    }

    public function test_has_emails_trait_relationships(): void
    {
        $parent = TestModel::create(['name' => 'Test Parent']);

        $this->service->store($parent, new EmailDto(
            type: 'personal',
            email: 'john@example.com',
            isPrimary: true,
        ));

        $this->service->store($parent, new EmailDto(
            type: 'work',
            email: 'john@company.com',
        ));

        $parent = $parent->fresh();

        $this->assertCount(2, $parent->emails);
        $this->assertNotNull($parent->primaryEmail);
        $this->assertEquals('john@example.com', $parent->primaryEmail->email);
        $this->assertCount(1, $parent->emailsOfType('work')->get());
    }

    public function test_mark_as_primary(): void
    {
        $parent = TestModel::create(['name' => 'Test Parent']);

        $email1 = $this->service->store($parent, new EmailDto(
            type: 'personal',
            email: 'john@example.com',
            isPrimary: true,
        ));

        $email2 = $this->service->store($parent, new EmailDto(
            type: 'work',
            email: 'john@company.com',
        ));

        $email2->markAsPrimary();

        $this->assertTrue($email2->fresh()->is_primary);
        $this->assertFalse($email1->fresh()->is_primary);
    }

    public function test_domain_attribute(): void
    {
        $parent = TestModel::create(['name' => 'Test Parent']);

        $email = $this->service->store($parent, new EmailDto(
            type: 'work',
            email: 'john@gmail.com',
        ));

        $this->assertEquals('gmail.com', $email->domain);
    }

    public function test_mark_as_verified(): void
    {
        $parent = TestModel::create(['name' => 'Test Parent']);

        $email = $this->service->store($parent, new EmailDto(
            type: 'personal',
            email: 'john@example.com',
        ));

        $this->assertFalse($email->is_verified);
        $this->assertNull($email->verified_at);

        $email->markAsVerified();

        $email = $email->fresh();
        $this->assertTrue($email->is_verified);
        $this->assertNotNull($email->verified_at);
    }

    public function test_verified_scope(): void
    {
        $parent = TestModel::create(['name' => 'Test Parent']);

        $this->service->store($parent, new EmailDto(
            type: 'personal',
            email: 'unverified@example.com',
        ));

        $verifiedEmail = $this->service->store($parent, new EmailDto(
            type: 'work',
            email: 'verified@example.com',
            isVerified: true,
        ));

        $verifiedEmails = Email::verified()->get();

        $this->assertCount(1, $verifiedEmails);
        $this->assertEquals($verifiedEmail->id, $verifiedEmails->first()->id);
    }
}
