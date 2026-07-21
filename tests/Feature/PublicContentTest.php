<?php

namespace Tests\Feature;

use App\Models\Page;
use App\Models\TicketDepartment;
use App\Models\User;
use Database\Seeders\LegalPageSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class PublicContentTest extends TestCase
{
    use RefreshDatabase;

    public function test_support_center_and_department_chooser_are_public(): void
    {
        $visible = TicketDepartment::query()->create([
            'name' => 'General Support',
            'description' => 'Help with active products and services.',
            'sort_order' => 1,
        ]);
        TicketDepartment::query()->create([
            'name' => 'Internal Escalations',
            'hidden' => true,
            'sort_order' => 2,
        ]);

        $this->get('/support')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Client/Support/Landing')
                ->has('departments', 1)
                ->where('departments.0.id', $visible->id)
                ->where('seo.canonical_url', route('support.center')));

        $this->get('/support/ticket')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Client/Support/Departments')
                ->has('departments', 1)
                ->where('departments.0.name', 'General Support'));
    }

    public function test_selected_public_department_carries_into_ticket_form(): void
    {
        $user = User::factory()->create();
        $first = TicketDepartment::query()->create(['name' => 'Sales', 'sort_order' => 1]);
        $selected = TicketDepartment::query()->create(['name' => 'Technical Support', 'sort_order' => 2]);

        $this->actingAs($user)
            ->get("/client-area/tickets/create?department={$selected->id}")
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Client/Support/Create')
                ->has('departments', 2)
                ->where('selectedDepartmentId', $selected->id)
                ->where('departments.0.id', $first->id));
    }

    public function test_software_development_page_is_public_and_seo_ready(): void
    {
        $this->get('/software-development')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Client/SoftwareDevelopment')
                ->where('seo.canonical_url', route('software-development'))
                ->where('seo.schema_json.@type', 'Service'));
    }

    public function test_legal_pages_are_seeded_and_available_on_clean_urls(): void
    {
        $this->seed(LegalPageSeeder::class);

        foreach (['terms-of-service', 'privacy-policy', 'refund-policy'] as $slug) {
            $this->get("/{$slug}")
                ->assertOk()
                ->assertInertia(fn (Assert $page) => $page
                    ->component('Client/Pages/Show')
                    ->where('managedPage.slug', $slug)
                    ->where('managedPage.template', 'legal')
                    ->where('managedPage.seo.canonical_url', route('legal.show', $slug)));
        }

        $this->assertSame(3, Page::query()->where('template', 'legal')->count());
    }
}
