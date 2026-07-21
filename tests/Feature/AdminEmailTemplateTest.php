<?php

namespace Tests\Feature;

use App\Mail\EmailOtpMail;
use App\Models\Admin;
use App\Models\EmailTemplate;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class AdminEmailTemplateTest extends TestCase
{
    use RefreshDatabase;

    public function test_default_system_templates_are_seeded(): void
    {
        $this->actingAs($this->admin(), 'admin');

        $this->get('/admin/settings/emailtemplates')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Configuration/Settings/EmailTemplates/Index')
                ->has('templates', 8)
                ->has('categories'));

        $this->assertTrue(
            EmailTemplate::query()->where('slug', 'email-otp')->where('is_system', true)->exists(),
        );
        $this->assertTrue(
            EmailTemplate::query()->where('slug', 'invoice-notification')->where('is_system', true)->exists(),
        );
        $this->assertTrue(
            EmailTemplate::query()->where('slug', 'subscription-renewed')->where('is_system', true)->exists(),
        );
        $this->assertTrue(
            EmailTemplate::query()->where('slug', 'subscription-payment-failed')->where('is_system', true)->exists(),
        );
        $this->assertTrue(
            EmailTemplate::query()->where('slug', 'subscription-cancellation-scheduled')->where('is_system', true)->exists(),
        );
        $this->assertTrue(
            EmailTemplate::query()->where('slug', 'subscription-renewal-reminder')->where('is_system', true)->exists(),
        );
        $this->assertTrue(
            EmailTemplate::query()->where('slug', 'refund-request-received')->where('is_system', true)->exists(),
        );
        $this->assertTrue(
            EmailTemplate::query()->where('slug', 'refund-request-decision')->where('is_system', true)->exists(),
        );
    }

    public function test_admin_can_create_and_edit_a_custom_template(): void
    {
        $this->actingAs($this->admin(), 'admin');

        $this->post('/admin/settings/emailtemplates', [
            'name' => 'Welcome Email',
            'category' => 'general',
            'subject' => 'Welcome to {{company_name}}',
            'body' => '<p>Hi {{client_name}}, welcome aboard!</p>',
            'enabled' => true,
        ])->assertRedirectContains('/admin/settings/emailtemplates/');

        $template = EmailTemplate::query()->where('slug', 'welcome-email')->sole();
        $this->assertFalse($template->is_system);

        $this->put("/admin/settings/emailtemplates/{$template->id}", [
            'name' => 'Welcome Aboard',
            'category' => 'product',
            'subject' => 'Welcome aboard!',
            'body' => '<p>Updated body</p>',
            'enabled' => false,
        ])->assertRedirect("/admin/settings/emailtemplates/{$template->id}");

        $template->refresh();
        $this->assertSame('Welcome Aboard', $template->name);
        $this->assertSame('product', $template->category);
        $this->assertFalse($template->enabled);

        $this->get("/admin/settings/emailtemplates/{$template->id}")
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Configuration/Settings/EmailTemplates/Edit')
                ->where('template.name', 'Welcome Aboard')
                ->has('mergeFields'));
    }

    public function test_system_template_identity_is_locked_but_content_is_editable(): void
    {
        $this->actingAs($this->admin(), 'admin');
        $template = EmailTemplate::query()->where('slug', 'email-otp')->sole();

        $this->put("/admin/settings/emailtemplates/{$template->id}", [
            'name' => 'Renamed Template',
            'category' => 'support',
            'subject' => 'Custom OTP subject {{otp_code}}',
            'body' => '<p>Your code: {{otp_code}}</p>',
            'enabled' => true,
        ])->assertRedirect("/admin/settings/emailtemplates/{$template->id}");

        $template->refresh();
        $this->assertSame('Email Verification Code', $template->name);
        $this->assertSame('general', $template->category);
        $this->assertSame('Custom OTP subject {{otp_code}}', $template->subject);
    }

    public function test_system_templates_cannot_be_deleted_but_custom_ones_can(): void
    {
        $this->actingAs($this->admin(), 'admin');
        $system = EmailTemplate::query()->where('slug', 'email-otp')->sole();

        $this->delete("/admin/settings/emailtemplates/{$system->id}")
            ->assertSessionHasErrors('template');
        $this->assertTrue(EmailTemplate::query()->whereKey($system->id)->exists());

        $custom = EmailTemplate::query()->create([
            'name' => 'Disposable',
            'slug' => 'disposable',
            'category' => 'general',
            'subject' => 'Bye',
            'body' => '<p>Bye</p>',
            'enabled' => true,
            'is_system' => false,
        ]);

        $this->delete("/admin/settings/emailtemplates/{$custom->id}")
            ->assertRedirect('/admin/settings/emailtemplates');
        $this->assertFalse(EmailTemplate::query()->whereKey($custom->id)->exists());
    }

    public function test_mailables_render_the_edited_template_with_merge_fields(): void
    {
        EmailTemplate::query()->where('slug', 'email-otp')->update([
            'subject' => 'Code {{otp_code}} for {{client_name}}',
            'body' => '<p>Hello {{client_name}}, your code is {{otp_code}}.</p>',
        ]);

        $user = User::factory()->create(['name' => 'Rifat']);
        $mail = new EmailOtpMail($user, '424242');

        $this->assertSame('Code 424242 for Rifat', $mail->envelope()->subject);
        $this->assertStringContainsString(
            'Hello Rifat, your code is 424242.',
            $mail->render(),
        );
    }

    public function test_disabled_template_falls_back_to_the_builtin_view(): void
    {
        EmailTemplate::query()->where('slug', 'email-otp')->update(['enabled' => false]);

        $user = User::factory()->create(['name' => 'Rifat']);
        $mail = new EmailOtpMail($user, '424242');

        $this->assertStringContainsString('is your', (string) $mail->envelope()->subject);
        $this->assertStringContainsString('424242', $mail->render());
    }

    private function admin(): Admin
    {
        return Admin::query()->create([
            'name' => 'Template Admin',
            'email' => 'templates@example.com',
            'password' => 'a-secure-password',
        ]);
    }
}
