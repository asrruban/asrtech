<?php

namespace App\Http\Controllers\Client;

use App\Enums\LicenseStatus;
use App\Http\Controllers\Controller;
use App\Models\License;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;

class LicenseReissueController extends Controller
{
    public function __invoke(Request $request, License $license): RedirectResponse
    {
        abort_unless($license->user_id === $request->user()?->id, 403);
        abort_if($license->status === LicenseStatus::Terminated, 403);

        $license->reissue();

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('License :key reissued — activate it on your new installation.', ['key' => $license->license_key]),
        ]);

        return redirect()->route('account.index');
    }
}
