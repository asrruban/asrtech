<?php

namespace Tests\Feature;

use App\Enums\AdminRole;
use App\Enums\QuoteStatus;
use App\Models\Admin;
use App\Models\Category;
use App\Models\MaintenancePlan;
use App\Models\MaintenanceRequest;
use App\Models\Product;
use App\Models\Project;
use App\Models\ProjectInquiry;
use App\Models\Quote;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ProjectWorkspaceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    public function test_inquiry_workflow_requires_explicit_client_and_billing_permission_and_is_idempotent(): void
    {
        Mail::fake();
        $client = User::factory()->create();
        $inquiry = $this->inquiry(['email' => $client->email]);
        $admin = $this->admin();
        $product = $this->product();
        $payload = ['product_id' => $product->id, 'unit_price' => 125, 'quantity' => 2, 'billing_cycle' => 'one_time', 'tax_rate' => 5];
        $this->actingAs($admin, 'admin')->post("/admin/inquiries/{$inquiry->id}/quote", $payload)->assertSessionHasErrors('quote');
        $this->assertSame(0, Quote::query()->count());
        $this->assertNull($inquiry->fresh()->user_id);
        $support = $this->admin(AdminRole::Support);
        $this->actingAs($support, 'admin')->patch("/admin/inquiries/{$inquiry->id}", [
            'status' => 'reviewed', 'user_id' => $client->id, 'assigned_admin_id' => $support->id,
            'internal_notes' => 'Identity checked separately.', 'follow_up_at' => now()->addDay()->toDateString(),
        ])->assertSessionHasNoErrors()->assertRedirect();
        $this->actingAs($support, 'admin')->post("/admin/inquiries/{$inquiry->id}/quote", $payload)->assertForbidden();
        $this->actingAs($admin, 'admin')->post("/admin/inquiries/{$inquiry->id}/quote", $payload)->assertSessionHasNoErrors()->assertRedirect();
        $quote = Quote::query()->sole();
        $this->assertSame(QuoteStatus::Draft, $quote->status);
        $this->assertSame('262.50', $quote->total);
        $this->assertSame($quote->id, $inquiry->fresh()->quote_id);
        $this->assertNull($quote->order_id);
        $this->post("/admin/inquiries/{$inquiry->id}/quote", $payload)->assertSessionHasNoErrors();
        $this->assertSame(1, Quote::query()->count());
        $this->patch("/admin/inquiries/{$inquiry->id}", ['status' => 'quoted', 'user_id' => User::factory()->create()->id])->assertSessionHasErrors('user_id');
        $this->assertDatabaseHas('admin_audit_logs', ['action' => 'inquiry.quote_linked', 'subject_id' => $inquiry->id]);
        Mail::assertNothingOutgoing();
    }

    public function test_inquiry_links_only_quotes_for_selected_client_and_due_filters_work(): void
    {
        $client = User::factory()->create();
        $other = User::factory()->create();
        $inquiry = $this->inquiry(['user_id' => $client->id, 'follow_up_at' => now()->subDay()]);
        $otherQuote = $this->quote($other);
        $this->actingAs($this->admin(), 'admin')->post("/admin/inquiries/{$inquiry->id}/quote", ['quote_id' => $otherQuote->id])->assertSessionHasErrors('quote_id');
        $quote = $this->quote($client);
        $this->post("/admin/inquiries/{$inquiry->id}/quote", ['quote_id' => $quote->id])->assertSessionHasNoErrors();
        $this->assertSame($quote->id, $inquiry->fresh()->quote_id);
        $this->inquiry(['status' => 'closed', 'follow_up_at' => now()->subDays(2)]);
        $this->get('/admin/inquiries?follow_up=due')->assertInertia(fn (Assert $page) => $page->component('Admin/Support/Inquiries/Index')->has('inquiries.data', 1));
        $this->get("/admin/inquiries/{$inquiry->id}")->assertOk();
    }

    public function test_admin_creates_a_client_project_from_inquiry_once_without_sending_mail(): void
    {
        Mail::fake();
        $client = User::factory()->create();
        $inquiry = $this->inquiry(['user_id' => $client->id]);
        $payload = ['user_id' => $client->id, 'project_inquiry_id' => $inquiry->id, 'title' => 'Client portal', 'description' => 'Agreed scope for delivery.'];
        $this->actingAs($this->admin(AdminRole::Support), 'admin')->post('/admin/projects', $payload)->assertSessionHasNoErrors()->assertRedirect();
        $project = Project::query()->sole();
        $this->post('/admin/projects', $payload)->assertRedirect("/admin/projects/{$project->id}");
        $this->assertSame(1, Project::query()->count());
        $this->assertSame('won', $inquiry->fresh()->status);
        $this->assertSame('planned', $project->status);
        $this->get('/admin/projects')->assertOk();
        $this->get("/admin/projects/{$project->id}")->assertOk();
        Mail::assertNothingOutgoing();
    }

    public function test_project_quote_source_must_be_accepted_and_belong_to_client(): void
    {
        $client = User::factory()->create();
        $quote = $this->quote($client);
        $payload = ['user_id' => $client->id, 'quote_id' => $quote->id, 'title' => 'Migration', 'description' => 'Agreed migration scope.'];
        $this->actingAs($this->admin(), 'admin')->post('/admin/projects', $payload)->assertSessionHasErrors('quote_id');
        $quote->update(['status' => QuoteStatus::Accepted]);
        $this->post('/admin/projects', [...$payload, 'user_id' => User::factory()->create()->id])->assertSessionHasErrors('quote_id');
        $this->post('/admin/projects', $payload)->assertSessionHasNoErrors();
        $this->post('/admin/projects', $payload)->assertSessionHasNoErrors();
        $this->assertSame(1, Project::query()->count());
    }

    public function test_clients_cannot_access_other_projects_or_cross_project_files_and_approvals(): void
    {
        Storage::fake('local');
        $owner = User::factory()->create();
        $intruder = User::factory()->create();
        $project = $this->project($owner);
        $other = $this->project($owner);
        $approval = $other->approvals()->create(['title' => 'Review', 'description' => 'Review delivery.', 'status' => 'pending']);
        $file = $other->files()->create(['original_name' => 'scope.txt', 'path' => 'private/scope.txt', 'mime_type' => 'text/plain', 'size' => 5]);
        Storage::disk('local')->put($file->path, 'scope');
        $this->actingAs($intruder)->get("/client-area/projects/{$project->id}")->assertNotFound();
        $this->post("/client-area/projects/{$project->id}/updates", ['body' => 'intrusion'])->assertNotFound();
        $this->post("/client-area/projects/{$project->id}/files", ['file' => UploadedFile::fake()->createWithContent('a.txt', 'data')])->assertNotFound();
        $this->get("/client-area/projects/{$other->id}/files/{$file->id}")->assertNotFound();
        $this->post("/client-area/projects/{$other->id}/approvals/{$approval->id}", ['status' => 'approved'])->assertNotFound();
        $this->flushSession();
        $this->actingAs($owner)->get('/client-area/projects')->assertOk()->assertInertia(fn (Assert $page) => $page->has('projects.data', 2));
        $this->get("/client-area/projects/{$project->id}/files/{$file->id}")->assertNotFound();
        $this->post("/client-area/projects/{$project->id}/approvals/{$approval->id}", ['status' => 'approved'])->assertNotFound();
        $this->get("/client-area/projects/{$other->id}/files/{$file->id}")->assertOk()->assertHeader('x-content-type-options', 'nosniff');
    }

    public function test_private_files_validate_type_size_and_hide_storage_paths(): void
    {
        Storage::fake('local');
        Storage::fake('public');
        $client = User::factory()->create();
        $project = $this->project($client);
        $this->actingAs($client)->post("/client-area/projects/{$project->id}/files", ['file' => UploadedFile::fake()->createWithContent('scope.txt', 'Project scope')])->assertSessionHasNoErrors();
        $file = $project->files()->sole();
        Storage::disk('local')->assertExists($file->path);
        Storage::disk('public')->assertMissing($file->path);
        $this->get('/storage/'.$file->path)->assertForbidden();
        $this->get("/client-area/projects/{$project->id}")->assertInertia(fn (Assert $page) => $page->missing('project.files.0.path'));
        $this->post("/client-area/projects/{$project->id}/files", ['file' => UploadedFile::fake()->createWithContent('script.php', '<?php echo 1;')])->assertSessionHasErrors('file');
        $this->post("/client-area/projects/{$project->id}/files", ['file' => UploadedFile::fake()->create('oversized.pdf', 10241, 'application/pdf')])->assertSessionHasErrors('file');
        $this->assertSame(1, $project->files()->count());
    }

    public function test_milestones_approvals_and_completion_follow_validated_transitions(): void
    {
        $client = User::factory()->create();
        $project = $this->project($client);
        $admin = $this->admin(AdminRole::Support);
        $base = "/admin/projects/{$project->id}";
        $this->actingAs($admin, 'admin')->post($base.'/milestones', ['title' => 'Review portal', 'due_date' => now()->addWeek()->toDateString()])->assertSessionHasNoErrors();
        $milestone = $project->milestones()->sole();
        $this->post($base.'/updates', ['body' => 'Build ready for review.'])->assertSessionHasNoErrors();
        $this->post($base.'/approvals', ['title' => 'Accept interface', 'description' => 'Please check the attached interface.'])->assertSessionHasNoErrors();
        $approval = $project->approvals()->sole();
        $completed = ['title' => $project->title, 'description' => $project->description, 'status' => 'completed'];
        $this->patch($base, $completed)->assertSessionHasErrors('status');
        $this->actingAs($client)->post("/client-area/projects/{$project->id}/approvals/{$approval->id}", ['status' => 'changes_requested'])->assertSessionHasErrors('response');
        $this->post("/client-area/projects/{$project->id}/approvals/{$approval->id}", ['status' => 'approved', 'response' => 'Looks good.'])->assertSessionHasNoErrors();
        $this->post("/client-area/projects/{$project->id}/approvals/{$approval->id}", ['status' => 'changes_requested', 'response' => 'Overwrite'])->assertSessionHasErrors('approval');
        $this->assertSame('approved', $approval->fresh()->status);
        $this->assertSame($client->id, $approval->fresh()->decided_by);
        $this->actingAs($admin, 'admin')->patch($base, $completed)->assertSessionHasErrors('status');
        $this->patch($base.'/milestones/'.$milestone->id, ['title' => $milestone->title, 'status' => 'completed'])->assertSessionHasNoErrors();
        $this->patch($base, $completed)->assertSessionHasNoErrors();
        $this->assertSame('completed', $project->fresh()->status);
        $this->post($base.'/approvals', ['title' => 'Closed', 'description' => 'No'])->assertSessionHasErrors('project');
        $this->actingAs($client)->post("/client-area/projects/{$project->id}/updates", ['body' => 'Closed'])->assertSessionHasErrors('project');
    }

    public function test_project_admin_permissions_and_authentication_are_enforced(): void
    {
        $project = $this->project(User::factory()->create());
        $this->get('/client-area/projects')->assertRedirect('/login');
        $this->actingAs(User::factory()->unverified()->create())->get('/client-area/projects')->assertRedirect('/verify-email');
        foreach ([AdminRole::Billing, AdminRole::Catalog] as $role) {
            $this->actingAs($this->admin($role), 'admin')->get('/admin/projects')->assertForbidden();
            $this->get("/admin/projects/{$project->id}")->assertForbidden();
            $this->post("/admin/projects/{$project->id}/updates", ['body' => 'blocked'])->assertForbidden();
        }
        $this->actingAs($this->admin(AdminRole::Support), 'admin')->get('/admin/projects')->assertOk();
    }

    public function test_linked_quote_ownership_cannot_be_changed_through_existing_billing_editor(): void
    {
        $client = User::factory()->create();
        $other = User::factory()->create();
        $product = $this->product();
        $payload = ['user_id' => $other->id, 'items' => [['product_id' => $product->id, 'quantity' => 1, 'unit_price' => 125, 'billing_cycle' => 'one_time']]];
        $quote = $this->quote($client);
        $this->inquiry(['user_id' => $client->id, 'quote_id' => $quote->id]);
        $this->actingAs($this->admin(), 'admin')->put("/admin/quotes/{$quote->id}", $payload)->assertSessionHasErrors('user_id');
        $this->assertSame($client->id, $quote->fresh()->user_id);
        $this->put("/admin/quotes/{$quote->id}", [...$payload, 'user_id' => $client->id])->assertSessionHasNoErrors();
        $projectQuote = $this->quote($client);
        $this->project($client)->update(['quote_id' => $projectQuote->id]);
        $this->put("/admin/quotes/{$projectQuote->id}", $payload)->assertSessionHasErrors('user_id');
        $maintenanceQuote = $this->quote($client);
        $plan = MaintenancePlan::query()->create(['name' => 'Agreed care', 'slug' => 'agreed-care', 'platform' => 'wordpress', 'summary' => 'Agreed maintenance.', 'scope' => 'Update and review.']);
        MaintenanceRequest::query()->create(['maintenance_plan_id' => $plan->id, 'user_id' => $client->id, 'plan_snapshot' => [], 'requirements' => 'Review website.', 'quote_id' => $maintenanceQuote->id, 'scope_acknowledged_at' => now()]);
        $this->put("/admin/quotes/{$maintenanceQuote->id}", $payload)->assertSessionHasErrors('user_id');
        $this->assertSame($client->id, $maintenanceQuote->fresh()->user_id);
        $unlinked = $this->quote($client);
        $this->put("/admin/quotes/{$unlinked->id}", $payload)->assertSessionHasNoErrors();
        $this->assertSame($other->id, $unlinked->fresh()->user_id);
    }

    public function test_cancelled_approvals_cannot_be_decided_and_closed_projects_can_be_explicitly_reopened(): void
    {
        $client = User::factory()->create();
        $project = $this->project($client);
        $approval = $project->approvals()->create(['title' => 'Review', 'description' => 'Review scope.', 'status' => 'pending']);
        $this->actingAs($this->admin(), 'admin')->delete("/admin/projects/{$project->id}/approvals/{$approval->id}")->assertSessionHasNoErrors();
        $this->assertSame('cancelled', $approval->fresh()->status);
        $this->actingAs($client)->post("/client-area/projects/{$project->id}/approvals/{$approval->id}", ['status' => 'approved'])->assertSessionHasErrors('approval');
        $this->actingAs($this->admin(), 'admin')->patch("/admin/projects/{$project->id}", ['title' => $project->title, 'description' => $project->description, 'status' => 'cancelled'])->assertSessionHasNoErrors();
        $this->post("/admin/projects/{$project->id}/milestones", ['title' => 'Closed milestone'])->assertSessionHasErrors('project');
        $this->patch("/admin/projects/{$project->id}", ['title' => $project->title, 'description' => $project->description, 'status' => 'active'])->assertSessionHasNoErrors();
        $this->post("/admin/projects/{$project->id}/milestones", ['title' => 'New milestone'])->assertSessionHasNoErrors();
        $this->assertSame('active', $project->fresh()->status);
    }

    private function admin(AdminRole $role = AdminRole::SuperAdmin): Admin
    {
        return Admin::query()->create(['name' => 'Project administrator', 'email' => fake()->unique()->safeEmail(), 'password' => 'password', 'role' => $role]);
    }

    private function inquiry(array $attributes = []): ProjectInquiry
    {
        return ProjectInquiry::query()->create([...['name' => 'Prospective client', 'email' => fake()->safeEmail(), 'service' => 'web-development', 'message' => 'Please build a customer portal.'], ...$attributes]);
    }

    private function project(User $user): Project
    {
        return Project::query()->create(['user_id' => $user->id, 'title' => 'Customer portal', 'description' => 'Agreed portal scope.', 'status' => 'active']);
    }

    private function quote(User $user): Quote
    {
        return Quote::query()->create(['user_id' => $user->id, 'quote_number' => 'Q-'.fake()->unique()->numerify('########'), 'status' => QuoteStatus::Draft, 'currency' => 'USD']);
    }

    private function product(): Product
    {
        $category = Category::query()->create(['name' => 'Development', 'slug' => 'development', 'status' => true]);

        return Product::query()->create(['category_id' => $category->id, 'name' => 'Portal development', 'slug' => 'portal-development', 'description' => 'Agreed work.', 'price' => 125, 'status' => true]);
    }
}
