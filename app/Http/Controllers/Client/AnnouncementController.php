<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\Announcement;
use Inertia\Inertia;
use Inertia\Response;

class AnnouncementController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Client/Announcements/Index', [
            'announcements' => Announcement::query()
                ->published()
                ->orderByDesc('published_at')
                ->get(['id', 'title', 'slug', 'excerpt', 'body', 'published_at'])
                ->map(fn (Announcement $announcement): array => [
                    'title' => $announcement->title,
                    'slug' => $announcement->slug,
                    'excerpt' => $announcement->excerpt ?? str($announcement->body)->limit(180)->toString(),
                    'published_at' => $announcement->published_at?->toIso8601String(),
                ]),
        ]);
    }

    public function show(Announcement $announcement): Response
    {
        abort_unless(
            $announcement->published
                && ($announcement->published_at === null || $announcement->published_at->isPast()),
            404,
        );

        return Inertia::render('Client/Announcements/Show', [
            'announcement' => [
                'title' => $announcement->title,
                'body' => $announcement->body,
                'published_at' => $announcement->published_at?->toIso8601String(),
            ],
            'more' => Announcement::query()
                ->published()
                ->whereKeyNot($announcement->id)
                ->orderByDesc('published_at')
                ->limit(3)
                ->get(['title', 'slug'])
                ->map(fn (Announcement $item): array => [
                    'title' => $item->title,
                    'slug' => $item->slug,
                ]),
        ]);
    }
}
