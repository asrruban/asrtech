<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Announcement;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AnnouncementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_publish_an_announcement(): void
    {
        $admin = Admin::query()->create([
            'name' => 'Site Admin',
            'email' => fake()->unique()->safeEmail(),
            'password' => 'a-secure-password',
        ]);

        $this->actingAs($admin, 'admin')
            ->post('/admin/announcements', [
                'title' => 'New Gateway Released',
                'excerpt' => 'bKash is here.',
                'body' => 'We now support bKash tokenized checkout.',
                'published' => true,
            ])
            ->assertRedirect('/admin/announcements');

        $announcement = Announcement::query()->sole();
        $this->assertSame('new-gateway-released', $announcement->slug);
        $this->assertNotNull($announcement->published_at);
    }

    public function test_published_announcements_are_public_and_drafts_are_hidden(): void
    {
        Announcement::query()->create([
            'title' => 'Public News',
            'body' => 'Visible body',
            'published' => true,
        ]);
        $draft = Announcement::query()->create([
            'title' => 'Secret Draft',
            'body' => 'Hidden body',
            'published' => false,
        ]);

        $this->get('/announcements')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Client/Announcements/Index')
                ->has('announcements', 1)
                ->where('announcements.0.title', 'Public News'));

        $this->get('/announcements/public-news')->assertOk();
        $this->get("/announcements/{$draft->slug}")->assertNotFound();
    }
}
