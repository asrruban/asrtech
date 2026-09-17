<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Models\ApiToken;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ApiTokenController extends Controller
{
    public function index(Request $request): Response
    {
        return Inertia::render('Admin/Configuration/ApiTokens', [
            'tokens' => ApiToken::query()
                ->with('admin:id,name')
                ->orderByDesc('created_at')
                ->get()
                ->map(fn (ApiToken $token): array => [
                    'id' => $token->id,
                    'name' => $token->name,
                    'created_by' => $token->admin?->name,
                    'last_used_at' => $token->last_used_at?->toIso8601String(),
                    'last_used_ip' => $token->last_used_ip,
                    'revoked' => $token->revoked_at !== null,
                    'created_at' => $token->created_at?->toIso8601String(),
                ]),
            // Shown exactly once after creation.
            'newToken' => $request->session()->pull('api.plain_token'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate(['name' => ['required', 'string', 'max:100']]);
        /** @var Admin $admin */
        $admin = $request->user('admin');

        [, $plain] = ApiToken::issue($admin, $data['name']);

        $request->session()->flash('api.plain_token', $plain);
        Inertia::flash('toast', ['type' => 'success', 'message' => __('API token created — copy it now, it will not be shown again.')]);

        return redirect()->route('admin.api-tokens.index');
    }

    public function destroy(ApiToken $apiToken): RedirectResponse
    {
        $apiToken->update(['revoked_at' => now()]);
        Inertia::flash('toast', ['type' => 'success', 'message' => __('API token revoked.')]);

        return redirect()->route('admin.api-tokens.index');
    }
}
