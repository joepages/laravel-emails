<?php

declare(strict_types=1);

namespace Emails\Tests\Unit;

use Emails\Http\Requests\EmailRequest;
use Emails\Tests\UnitTestCase;

class EmbeddedRulesTest extends UnitTestCase
{
    public function test_it_returns_rules_with_default_prefix(): void
    {
        $rules = EmailRequest::embeddedRules();

        $this->assertArrayHasKey('emails', $rules);
        $this->assertArrayHasKey('emails.*.email', $rules);
        $this->assertArrayHasKey('emails.*.id', $rules);
        $this->assertArrayHasKey('emails.*.type', $rules);
        $this->assertArrayHasKey('emails.*.is_primary', $rules);
        $this->assertArrayHasKey('emails.*.is_verified', $rules);
        $this->assertArrayHasKey('emails.*.verified_at', $rules);
        $this->assertArrayHasKey('emails.*.metadata', $rules);
    }

    public function test_it_returns_rules_with_custom_prefix(): void
    {
        $rules = EmailRequest::embeddedRules('contact_emails');

        $this->assertArrayHasKey('contact_emails', $rules);
        $this->assertArrayHasKey('contact_emails.*.email', $rules);

        // Ensure default prefix keys are not present
        $this->assertArrayNotHasKey('emails', $rules);
        $this->assertArrayNotHasKey('emails.*.email', $rules);
    }

    public function test_top_level_rule_is_sometimes_array(): void
    {
        $rules = EmailRequest::embeddedRules();

        $this->assertEquals(['sometimes', 'array'], $rules['emails']);
    }

    public function test_required_fields_have_required_rule(): void
    {
        $rules = EmailRequest::embeddedRules();

        $this->assertContains('required', $rules['emails.*.email']);
    }

    public function test_email_field_has_email_rule(): void
    {
        $rules = EmailRequest::embeddedRules();

        $this->assertContains('email', $rules['emails.*.email']);
    }

    public function test_id_field_is_optional_integer(): void
    {
        $rules = EmailRequest::embeddedRules();

        $this->assertContains('sometimes', $rules['emails.*.id']);
        $this->assertContains('integer', $rules['emails.*.id']);
        $this->assertContains('exists:emails,id', $rules['emails.*.id']);
    }
}
