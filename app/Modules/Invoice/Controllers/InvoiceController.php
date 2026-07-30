<?php

declare(strict_types=1);

namespace App\Modules\Invoice\Controllers;

use App\Core\Database\Connection;
use App\Core\Http\Request;
use App\Core\Http\Response;
use App\Modules\Invoice\Services\InvoicePdfService;
use App\Services\Auth\AuthService;

final class InvoiceController
{
    /** Customer: kendi faturasının PDF'ini indirir */
    public function customerPdf(Request $request): Response
    {
        $customer = AuthService::customer();
        $id = (int) $request->param('id');
        $inv = Connection::selectOne("SELECT id, customer_id, invoice_number FROM invoices WHERE id = ?", [$id]);
        if (!$inv || (int) $inv['customer_id'] !== (int) $customer['id']) {
            return Response::notFound('Fatura bulunamadı');
        }
        $pdf = InvoicePdfService::render($id);
        if ($pdf === null) return Response::notFound();

        return Response::make($pdf, 200, [
            'Content-Type'        => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="' . $inv['invoice_number'] . '.pdf"',
            'Content-Length'      => (string) strlen($pdf),
        ]);
    }

    /** Admin: herhangi bir faturanın PDF'ini indirir */
    public function adminPdf(Request $request): Response
    {
        $id = (int) $request->param('id');
        $inv = Connection::selectOne("SELECT id, invoice_number FROM invoices WHERE id = ?", [$id]);
        if (!$inv) return Response::notFound('Fatura bulunamadı');
        $pdf = InvoicePdfService::render($id);
        if ($pdf === null) return Response::notFound();

        return Response::make($pdf, 200, [
            'Content-Type'        => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="' . $inv['invoice_number'] . '.pdf"',
            'Content-Length'      => (string) strlen($pdf),
        ]);
    }
}
