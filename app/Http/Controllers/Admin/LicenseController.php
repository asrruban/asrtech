<?php

namespace App\Http\Controllers\Admin;

use App\Enums\LicenseStatus;
use App\Http\Controllers\Controller;
use App\Models\License;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class LicenseController extends Controller
{
    public function show(License $license): Response
    {
        $license->load([
            'user:id,name,email',
            'product:id,name,slug,type,version',
            'order:id,order_number,currency,amount,billing_cycle,payment_method,paid_at',
            'order.invoice:id,order_id,invoice_number,status',
        ]);

        return Inertia::render('Admin/Licenses/Show', [
            'license' => $license,
            'accessLogs' => $license->accessLogs()
                ->latest('id')
                ->take(15)
                ->get(['id', 'domain', 'ip_address', 'path', 'result', 'created_at']),
        ]);
    }

    public function update(Request $request, License $license): RedirectResponse
    {
        $data = $request->validate([
            'action' => ['required', Rule::in(['suspend', 'unsuspend', 'terminate', 'reissue', 'reset_reissues', 'update_installation'])],
            'domain' => ['nullable', 'string', 'max:1000'],
            'path' => ['nullable', 'string', 'max:1000'],
            'ip_address' => ['nullable', 'string', 'max:1000', function (string $attribute, mixed $value, callable $fail): void {
                foreach (explode(',', (string) $value) as $ip) {
                    if (trim($ip) !== '' && filter_var(trim($ip), FILTER_VALIDATE_IP) === false) {
                        $fail(__('Every IP address in the list must be valid.'));

                        return;
                    }
                }
            }],
        ]);

        if ($license->status === LicenseStatus::Terminated && $data['action'] !== 'reissue') {
            throw ValidationException::withMessages([
                'action' => __('Terminated licenses cannot be changed.'),
            ]);
        }

        $message = match ($data['action']) {
            'suspend' => $this->suspend($license),
            'unsuspend' => $this->unsuspend($license),
            'terminate' => $this->terminate($license),
            'reissue' => $this->reissue($license),
            'reset_reissues' => $this->resetReissues($license),
            'update_installation' => $this->updateInstallation($license, $data),
            default => abort(400),
        };

        Inertia::flash('toast', ['type' => 'success', 'message' => $message]);

        return redirect()->route('admin.licenses.show', $license);
    }

    private function suspend(License $license): string
    {
        $license->suspend();

        return __('License :key suspended.', ['key' => $license->license_key]);
    }

    private function unsuspend(License $license): string
    {
        $license->unsuspend();

        return __('License :key reactivated.', ['key' => $license->license_key]);
    }

    private function terminate(License $license): string
    {
        $license->terminate();

        return __('License :key terminated.', ['key' => $license->license_key]);
    }

    private function reissue(License $license): string
    {
        $license->reissue();

        return __('License :key reissued — it can now activate on a new installation.', ['key' => $license->license_key]);
    }

    private function resetReissues(License $license): string
    {
        $license->resetReissues();

        return __('Reissue counter reset for :key.', ['key' => $license->license_key]);
    }

    /** @param array<string, mixed> $data */
    private function updateInstallation(License $license, array $data): string
    {
        $license->update([
            'domain' => $data['domain'] ?? null,
            'path' => $data['path'] ?? null,
            'ip_address' => $data['ip_address'] ?? null,
        ]);

        return __('Installation details updated for :key.', ['key' => $license->license_key]);
    }
}
