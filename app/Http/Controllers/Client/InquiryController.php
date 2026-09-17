<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\ProjectInquiry;
use App\Services\InquiryNotificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Throwable;

class InquiryController extends Controller
{
    public function store(Request $request, InquiryNotificationService $notifications): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email:rfc', 'max:255'],
            'service' => ['required', Rule::in([...array_keys(config('asrtech.services', [])), 'not-sure'])],
            'message' => ['required', 'string', 'min:20', 'max:10000'],
        ]);

        try {
            $inquiry = ProjectInquiry::query()->create($data);
        } catch (Throwable $exception) {
            report($exception);

            return back()->withInput()->withErrors([
                'submission' => 'Your inquiry could not be saved. Please try again or contact us through our Facebook page.',
            ]);
        }

        try {
            $notifications->record($inquiry);
        } catch (Throwable $exception) {
            // The saved inquiry remains available even if email is unavailable.
            report($exception);
        }

        return redirect()->route('contact')->with('inquiry_received', true);
    }
}
