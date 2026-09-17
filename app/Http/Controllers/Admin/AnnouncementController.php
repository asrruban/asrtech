<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Announcement;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AnnouncementController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Admin/Content/Announcements', [
            'announcements' => Announcement::query()
                ->orderByDesc('created_at')
                ->get()
                ->map(fn (Announcement $announcement): array => [
                    'id' => $announcement->id,
                    'title' => $announcement->title,
                    'slug' => $announcement->slug,
                    'excerpt' => $announcement->excerpt,
                    'body' => $announcement->body,
                    'published' => $announcement->published,
                    'published_at' => $announcement->published_at?->toIso8601String(),
                    'created_at' => $announcement->created_at?->toIso8601String(),
                ]),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        Announcement::query()->create($this->validated($request));

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Announcement created.')]);

        return redirect()->route('admin.announcements.index');
    }

    public function update(Request $request, Announcement $announcement): RedirectResponse
    {
        $announcement->update($this->validated($request));

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Announcement updated.')]);

        return redirect()->route('admin.announcements.index');
    }

    public function destroy(Announcement $announcement): RedirectResponse
    {
        $announcement->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Announcement deleted.')]);

        return redirect()->route('admin.announcements.index');
    }

    /** @return array<string, mixed> */
    private function validated(Request $request): array
    {
        return $request->validate([
            'title' => ['required', 'string', 'max:200'],
            'excerpt' => ['nullable', 'string', 'max:500'],
            'body' => ['required', 'string', 'max:65535'],
            'published' => ['required', 'boolean'],
        ]);
    }
}
