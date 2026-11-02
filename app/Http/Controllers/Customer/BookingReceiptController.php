<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\TemuJanji;
use App\Support\BookingReceiptPdf;
use Illuminate\Http\Response;

class BookingReceiptController extends Controller
{
    public function __invoke(string $kodeBooking, BookingReceiptPdf $pdf): Response
    {
        $booking = TemuJanji::query()->with('gudang:id,unit_code,blok,tipe_gudang')
            ->where('kode_booking', $kodeBooking)->firstOrFail();

        return response($pdf->render($booking), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="bukti-'.$booking->kode_booking.'.pdf"',
            'Cache-Control' => 'no-store, private',
            'X-Content-Type-Options' => 'nosniff',
            'Referrer-Policy' => 'no-referrer',
        ]);
    }
}
