<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\SepayOrder;

class RetailInvoicePDFService
{
    public function getOrCreateInvoice(SepayOrder $order): Invoice
    {
        $invoice = Invoice::where('sepay_order_id', $order->id)->first();

        if ($invoice) {
            return $invoice;
        }

        if (! $order->isPaid()) {
            throw new \RuntimeException('Chỉ có thể in hóa đơn sau khi thanh toán thành công.');
        }

        return Invoice::createRetailFromOrder($order);
    }

    public function makePdf(Invoice $invoice)
    {
        return app('dompdf.wrapper')->loadView('pdf.retail-invoice', [
            'invoice' => $invoice,
            'items' => $invoice->seats['items'] ?? [],
        ])->setPaper('A4', 'portrait');
    }
}
