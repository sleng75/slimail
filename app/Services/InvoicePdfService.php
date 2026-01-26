<?php

namespace App\Services;

use App\Models\Invoice;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;

class InvoicePdfService
{
    /**
     * Generate PDF for an invoice
     */
    public function generate(Invoice $invoice): string
    {
        $invoice->load(['tenant', 'subscription.plan', 'payment']);

        $pdf = Pdf::loadView('invoices.pdf', [
            'invoice' => $invoice,
            'tenant' => $invoice->tenant,
            'subscription' => $invoice->subscription,
            'plan' => $invoice->subscription?->plan,
            'payment' => $invoice->payment,
        ]);

        $pdf->setPaper('A4', 'portrait');

        return $pdf->output();
    }

    /**
     * Generate and store PDF for an invoice
     */
    public function generateAndStore(Invoice $invoice): string
    {
        $pdfContent = $this->generate($invoice);

        $filename = $this->getFilename($invoice);
        $path = "invoices/{$invoice->tenant_id}/{$filename}";

        Storage::disk('local')->put($path, $pdfContent);

        // Update invoice with PDF path
        $invoice->update(['pdf_path' => $path]);

        return $path;
    }

    /**
     * Download PDF for an invoice
     */
    public function download(Invoice $invoice)
    {
        $invoice->load(['tenant', 'subscription.plan', 'payment']);

        $pdf = Pdf::loadView('invoices.pdf', [
            'invoice' => $invoice,
            'tenant' => $invoice->tenant,
            'subscription' => $invoice->subscription,
            'plan' => $invoice->subscription?->plan,
            'payment' => $invoice->payment,
        ]);

        $pdf->setPaper('A4', 'portrait');

        return $pdf->download($this->getFilename($invoice));
    }

    /**
     * Stream PDF for an invoice (inline display)
     */
    public function stream(Invoice $invoice)
    {
        $invoice->load(['tenant', 'subscription.plan', 'payment']);

        $pdf = Pdf::loadView('invoices.pdf', [
            'invoice' => $invoice,
            'tenant' => $invoice->tenant,
            'subscription' => $invoice->subscription,
            'plan' => $invoice->subscription?->plan,
            'payment' => $invoice->payment,
        ]);

        $pdf->setPaper('A4', 'portrait');

        return $pdf->stream($this->getFilename($invoice));
    }

    /**
     * Generate invoice number following OHADA format
     * Format: FACT-{YEAR}-{SEQUENCE}
     */
    public static function generateInvoiceNumber(int $tenantId): string
    {
        $year = date('Y');

        // Get the last invoice number for this tenant this year
        $lastInvoice = Invoice::where('tenant_id', $tenantId)
            ->whereYear('created_at', $year)
            ->orderBy('id', 'desc')
            ->first();

        if ($lastInvoice && preg_match('/FACT-' . $year . '-(\d+)/', $lastInvoice->number, $matches)) {
            $sequence = (int) $matches[1] + 1;
        } else {
            $sequence = 1;
        }

        return sprintf('FACT-%s-%05d', $year, $sequence);
    }

    /**
     * Get PDF filename
     */
    protected function getFilename(Invoice $invoice): string
    {
        return sprintf(
            'facture_%s_%s.pdf',
            $invoice->number,
            $invoice->created_at->format('Y-m-d')
        );
    }

    /**
     * Calculate invoice totals
     */
    public static function calculateTotals(float $subtotal, float $taxRate = 18): array
    {
        $taxAmount = $subtotal * ($taxRate / 100);
        $total = $subtotal + $taxAmount;

        return [
            'subtotal' => round($subtotal, 0),
            'tax_rate' => $taxRate,
            'tax_amount' => round($taxAmount, 0),
            'total' => round($total, 0),
        ];
    }
}
