<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\TicketDepartment;
use App\Models\TicketDepartmentField;
use App\Services\DepartmentMailProbe;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class AdminSupportDepartmentTest extends TestCase
{
    use RefreshDatabase;

    public function test_index_lists_departments_in_display_order(): void
    {
        $this->actingAs($this->admin(), 'admin');

        TicketDepartment::query()->create(['name' => 'Billing', 'sort_order' => 2]);
        TicketDepartment::query()->create(['name' => 'Support', 'sort_order' => 1]);

        $this->get('/admin/support/departments')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Support/Departments/Index')
                ->has('departments', 2)
                ->where('departments.0.name', 'Support')
                ->where('departments.1.name', 'Billing'));
    }

    public function test_admin_can_create_a_department_with_flags_and_assigned_admins(): void
    {
        $admin = $this->admin();
        $this->actingAs($admin, 'admin');

        $this->post('/admin/support/departments', [
            ...$this->validPayload(),
            'assigned_admin_ids' => [$admin->id],
            'clients_only' => true,
            'feedback_request' => true,
            'mail_hostname' => 'mail.asrhost.com',
            'mail_port' => 993,
            'mail_email' => 'support@asrhost.com',
            'mail_password' => 'top-secret',
        ])->assertRedirectContains('/admin/support/departments/');

        $department = TicketDepartment::query()->where('name', 'Support')->sole();
        $this->assertTrue($department->clients_only);
        $this->assertTrue($department->feedback_request);
        $this->assertFalse($department->hidden);
        $this->assertSame('top-secret', $department->mail_password);
        $this->assertSame([$admin->id], $department->admins()->pluck('admins.id')->all());

        // The password is encrypted at rest.
        $raw = DB::table('ticket_departments')->where('id', $department->id)->value('mail_password');
        $this->assertNotSame('top-secret', $raw);
    }

    public function test_edit_page_exposes_configured_flags_but_never_secrets(): void
    {
        $this->actingAs($this->admin(), 'admin');

        $department = TicketDepartment::query()->create([
            ...$this->validPayload(),
            'mail_password' => 'top-secret',
        ]);

        $this->get("/admin/support/departments/{$department->id}/edit")
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Support/Departments/Edit')
                ->where('department.name', 'Support')
                ->where('department.mail_password_configured', true)
                ->where('department.mail_client_secret_configured', false)
                ->missing('department.mail_password')
                ->missing('department.mail_client_secret')
                ->has('admins')
                ->has('fields')
                ->has('fieldTypes')
                ->has('mailProviders'));
    }

    public function test_blank_password_keeps_stored_value_and_filled_password_replaces_it(): void
    {
        $admin = $this->admin();
        $this->actingAs($admin, 'admin');

        $department = TicketDepartment::query()->create([
            ...$this->validPayload(),
            'mail_password' => 'original',
        ]);

        $this->put("/admin/support/departments/{$department->id}", [
            ...$this->validPayload(),
            'name' => 'Renamed',
            'assigned_admin_ids' => [$admin->id],
            'mail_password' => '',
        ])->assertRedirect("/admin/support/departments/{$department->id}/edit");

        $department->refresh();
        $this->assertSame('Renamed', $department->name);
        $this->assertSame('original', $department->mail_password);
        $this->assertSame([$admin->id], $department->admins()->pluck('admins.id')->all());

        $this->put("/admin/support/departments/{$department->id}", [
            ...$this->validPayload(),
            'mail_password' => 'replaced',
            'assigned_admin_ids' => [],
        ]);

        $department->refresh();
        $this->assertSame('replaced', $department->mail_password);
        $this->assertSame([], $department->admins()->pluck('admins.id')->all());
    }

    public function test_departments_can_be_reordered_and_deleted(): void
    {
        $this->actingAs($this->admin(), 'admin');

        $first = TicketDepartment::query()->create(['name' => 'First', 'sort_order' => 1]);
        $second = TicketDepartment::query()->create(['name' => 'Second', 'sort_order' => 2]);

        $this->post("/admin/support/departments/{$second->id}/move", ['direction' => 'up'])
            ->assertRedirect('/admin/support/departments');

        $this->assertTrue($second->refresh()->sort_order < $first->refresh()->sort_order);

        $this->delete("/admin/support/departments/{$first->id}")
            ->assertRedirect('/admin/support/departments');
        $this->assertFalse(TicketDepartment::query()->whereKey($first->id)->exists());
    }

    public function test_custom_fields_can_be_managed(): void
    {
        $this->actingAs($this->admin(), 'admin');
        $department = TicketDepartment::query()->create($this->validPayload());

        // Drop downs need options.
        $this->post("/admin/support/departments/{$department->id}/fields", [
            'name' => 'Priority',
            'type' => 'dropdown',
            'required' => true,
            'admin_only' => false,
        ])->assertSessionHasErrors('select_options');

        $this->post("/admin/support/departments/{$department->id}/fields", [
            'name' => 'Priority',
            'type' => 'dropdown',
            'select_options' => "Low\nMedium\nHigh",
            'description' => 'How urgent is this?',
            'required' => true,
            'admin_only' => false,
            'sort_order' => 1,
        ])->assertRedirect("/admin/support/departments/{$department->id}/edit");

        $field = $department->fields()->sole();
        $this->assertSame('dropdown', $field->type);
        $this->assertTrue($field->required);

        // Changing the type away from dropdown clears stale options.
        $this->put("/admin/support/departments/{$department->id}/fields/{$field->id}", [
            'name' => 'Order Number',
            'type' => 'text',
            'validation' => '/^[0-9]+$/',
            'required' => false,
            'admin_only' => true,
        ])->assertRedirect("/admin/support/departments/{$department->id}/edit");

        $field->refresh();
        $this->assertSame('Order Number', $field->name);
        $this->assertNull($field->select_options);
        $this->assertTrue($field->admin_only);

        $this->delete("/admin/support/departments/{$department->id}/fields/{$field->id}");
        $this->assertSame(0, $department->fields()->count());
    }

    public function test_custom_field_routes_are_scoped_to_their_department(): void
    {
        $this->actingAs($this->admin(), 'admin');

        $department = TicketDepartment::query()->create($this->validPayload());
        $other = TicketDepartment::query()->create(['name' => 'Other']);
        $field = $other->fields()->create([
            'name' => 'Foreign',
            'type' => 'text',
        ]);

        $this->delete("/admin/support/departments/{$department->id}/fields/{$field->id}")
            ->assertNotFound();
        $this->assertTrue(TicketDepartmentField::query()->whereKey($field->id)->exists());
    }

    public function test_mail_test_uses_the_stored_password_when_left_blank(): void
    {
        $this->actingAs($this->admin(), 'admin');

        $department = TicketDepartment::query()->create([
            ...$this->validPayload(),
            'mail_password' => 'stored-secret',
        ]);

        $this->mock(DepartmentMailProbe::class)
            ->expects('test')
            ->with('mail.asrhost.com', 993, 'support@asrhost.com', 'stored-secret')
            ->andReturnNull();

        $this->from("/admin/support/departments/{$department->id}/edit")
            ->post('/admin/support/departments/test-mail', [
                'department_id' => $department->id,
                'mail_hostname' => 'mail.asrhost.com',
                'mail_port' => 993,
                'mail_email' => 'support@asrhost.com',
                'mail_password' => '',
            ])->assertRedirect("/admin/support/departments/{$department->id}/edit");
    }

    public function test_mail_test_requires_a_password_from_somewhere(): void
    {
        $this->actingAs($this->admin(), 'admin');

        // No department, no submitted password — the probe must never run.
        $this->mock(DepartmentMailProbe::class)
            ->shouldNotReceive('test');

        $this->from('/admin/support/departments/create')
            ->post('/admin/support/departments/test-mail', [
                'mail_hostname' => 'mail.asrhost.com',
                'mail_port' => 993,
                'mail_email' => 'support@asrhost.com',
            ])->assertRedirect('/admin/support/departments/create');
    }

    public function test_department_name_is_required(): void
    {
        $this->actingAs($this->admin(), 'admin');

        $this->post('/admin/support/departments', [
            ...$this->validPayload(),
            'name' => '',
        ])->assertSessionHasErrors('name');
    }

    private function admin(): Admin
    {
        return Admin::query()->create([
            'name' => 'Support Admin',
            'email' => 'support-admin@example.com',
            'password' => 'a-secure-password',
        ]);
    }

    /** @return array<string, mixed> */
    private function validPayload(): array
    {
        return [
            'name' => 'Support',
            'description' => 'Questions answered about your existing services',
            'email' => 'support@asrhost.com',
            'clients_only' => false,
            'pipe_replies_only' => false,
            'no_autoresponder' => false,
            'feedback_request' => false,
            'prevent_client_closure' => false,
            'hidden' => false,
            'mail_provider' => 'pop3imap',
        ];
    }
}
