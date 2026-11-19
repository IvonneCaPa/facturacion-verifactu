<?php

use App\Models\Factura;
use App\Services\PdfInvoiceService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => redirect('/admin'));

Route::middleware('auth')->group(function () {
    Route::get('/facturas/{factura}/pdf', function (Factura $factura, PdfInvoiceService $pdfService) {
        // Seguridad multi-autónomo: impedir que un usuario vea facturas de otro emisor
        abort_unless($factura->emisor_id === Auth::user()?->emisor?->id, 403);

        return $pdfService->stream($factura); // Usa ->download($factura) si prefieres descarga directa
    })->name('facturas.pdf');
});