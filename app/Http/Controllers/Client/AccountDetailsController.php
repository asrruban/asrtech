<?php

namespace App\Http\Controllers\Client;

use App\Enums\InvoiceStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Client\UpdateAccountDetailsRequest;
use App\Models\Invoice;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;
use Inertia\Response;

/**
 * WHMCS-style "My Account": edit account/billing details and change
 * the password, presented alongside the account summary card.
 */
class AccountDetailsController extends Controller
{
    public function edit(Request $request): Response
    {
        $user = $this->user($request);
        [$firstName, $lastName] = $this->splitName($user->name);

        return Inertia::render('Client/Account/Details', [
            ...$this->summary($user),
            'details' => [
                'first_name' => $firstName,
                'last_name' => $lastName,
                'company_name' => $user->company_name,
                'address_1' => $user->address_1,
                'city' => $user->city,
                'state' => $user->state,
                'postcode' => $user->postcode,
                'country' => $user->country,
                'phone' => $user->phone,
                'email' => $user->email,
                'vat_number' => $user->vat_number,
                'newsletter' => $user->newsletter,
                'account_id' => $user->id,
            ],
        ]);
    }

    public function update(UpdateAccountDetailsRequest $request): RedirectResponse
    {
        $user = $this->user($request);
        $data = $request->validated();

        $emailChanged = $data['email'] !== $user->email;

        $user->fill([
            'name' => trim($data['first_name'].' '.$data['last_name']),
            'company_name' => $data['company_name'] ?? null,
            'address_1' => $data['address_1'],
            'city' => $data['city'],
            'state' => $data['state'] ?? null,
            'postcode' => $data['postcode'],
            'country' => strtoupper($data['country']),
            'phone' => $data['phone'],
            'email' => $data['email'],
            'vat_number' => $data['vat_number'] ?? null,
            'newsletter' => $data['newsletter'],
        ]);

        // A new email address must be verified again (OTP flow).
        if ($emailChanged) {
            $user->email_verified_at = null;
        }

        $user->save();

        Inertia::flash('toast', ['type' => 'success', 'message' => $emailChanged
            ? __('Account details saved — please verify your new email address.')
            : __('Account details saved.')]);

        return redirect()->route('profile.edit');
    }

    public function editPassword(Request $request): Response
    {
        $user = $this->user($request);

        return Inertia::render('Client/Account/ChangePassword', [
            ...$this->summary($user),
            // Social sign-in accounts have no password yet — no current
            // password is asked when setting the first one.
            'hasPassword' => filled($user->password),
        ]);
    }

    public function updatePassword(Request $request): RedirectResponse
    {
        $user = $this->user($request);

        $data = $request->validate([
            'current_password' => filled($user->password)
                ? ['required', 'current_password']
                : ['nullable'],
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        $user->update(['password' => $data['password']]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Password changed.')]);

        return redirect()->route('password.edit');
    }

    /** Delete the account entirely (WHMCS-style closure). */
    public function destroy(Request $request): RedirectResponse
    {
        $request->validate([
            'password' => ['required', 'current_password'],
        ]);

        $user = $this->user($request);

        Auth::guard('web')->logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }

    private function user(Request $request): User
    {
        $user = $request->user();
        abort_unless($user instanceof User, 403);

        return $user;
    }

    /** @return array{0: string, 1: string} */
    private function splitName(string $name): array
    {
        $parts = explode(' ', trim($name), 2);

        return [$parts[0], $parts[1] ?? ''];
    }

    /**
     * Account summary card data, matching the dashboard card.
     *
     * @return array<string, mixed>
     */
    private function summary(User $user): array
    {
        $due = Invoice::query()
            ->where('status', InvoiceStatus::Issued)
            ->whereHas('order', fn (Builder $query) => $query->where('user_id', $user->id))
            ->with('order:id,amount,setup_fee,tax_amount')
            ->get()
            ->sum(fn (Invoice $invoice) => $invoice->order->totalAmount());

        return [
            'account' => [
                'name' => $user->name,
                'email' => $user->email,
                'address' => array_values(array_filter([
                    $user->address_1,
                    $user->address_2,
                    trim(implode(' ', array_filter([$user->postcode, $user->city]))),
                    trim(implode(', ', array_filter([$user->state, $user->country]))),
                ])),
            ],
            'totalDue' => number_format($due, 2, '.', ''),
            'currency' => config('asrtech.currency', 'USD'),
        ];
    }
}
