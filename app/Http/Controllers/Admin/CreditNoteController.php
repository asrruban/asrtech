<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CreditNote;
use Barryvdh\DomPDF\Facade\Pdf;
use Symfony\Component\HttpFoundation\Response;

class CreditNoteController extends Controller
{
    public function download(CreditNote $creditNote): Response
    {
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
}
