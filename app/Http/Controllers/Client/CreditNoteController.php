<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\CreditNote;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

class CreditNoteController extends Controller
{
    public function show(Request $request, CreditNote $creditNote): Response
    {
        $this->authorizeCustomer($request, $creditNote);
        $creditNote->load(['refund', 'invoice.order.product:id,name']);

        return Inertia::render('Client/Account/CreditNote', [
            'creditNote' => $creditNote,
        ]);
    }

    public function download(Request $request, CreditNote $creditNote): SymfonyResponse
    {
        $this->authorizeCustomer($request, $creditNote);
        $creditNote->load([
            'refund.admin:id,name',
            'invoice.order.user:id,name,email',
            'invoice.order.product:id,name',
        ]);

        return Pdf::loadView('pdf.credit-note', [
            'creditNote' => $creditNote,
            'payTo' => config('asrtech.invoice.pay_to'),
        ])->download("{$creditNote->credit_note_number}.pdf");
    }

    private function authorizeCustomer(Request $request, CreditNote $creditNote): void
    {
        $user = $request->user();
        abort_unless($user instanceof User && $creditNote->invoice->order->user_id === $user->id, 404);
    }
}
