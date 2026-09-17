<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Client\Concerns\BuildsAccountSummary;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;
use Inertia\Inertia;
use Inertia\Response;

class NotificationController extends Controller
{
    use BuildsAccountSummary;

    public function index(Request $request): Response
    {
        /** @var User $user */
        $user = $request->user();

        $notifications = $user->notifications()
            ->latest()
            ->paginate(20)
            ->through(fn (DatabaseNotification $notification): array => [
                'id' => $notification->id,
                'title' => $notification->data['title'] ?? '',
                'message' => $notification->data['message'] ?? '',
                'url' => $notification->data['url'] ?? null,
                'level' => $notification->data['level'] ?? 'info',
                'read' => $notification->read_at !== null,
                'created_at' => $notification->created_at?->toIso8601String(),
            ]);

        return Inertia::render('Client/Account/Notifications', [
            ...$this->accountSummary($user),
            'notifications' => $notifications,
        ]);
    }

    public function read(Request $request, string $notification): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();

        /** @var DatabaseNotification $record */
        $record = $user->notifications()->where('id', $notification)->firstOrFail();
        $record->markAsRead();

        $url = $record->data['url'] ?? null;

        return is_string($url) && str_starts_with($url, '/')
            ? redirect($url)
            : redirect()->route('account.notifications');
    }

    public function readAll(Request $request): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();
        $user->unreadNotifications->markAsRead();

        return redirect()->route('account.notifications');
    }
}
