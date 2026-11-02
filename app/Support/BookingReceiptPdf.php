<?php

namespace App\Support;

use App\Models\TemuJanji;

class BookingReceiptPdf
{
    public function render(TemuJanji $booking): string
    {
        $lines = [
            'SARANA INDUSTRIAL POINT - BUKTI PENGAJUAN TEMU JANJI',
            '',
            'Kode Booking: '.$booking->kode_booking,
            'Unit Gudang: '.($booking->gudang?->unit_code ?? 'Tidak tersedia'),
            'Blok: '.($booking->gudang?->blok ?? '-'),
            'Tipe Gudang: '.($booking->gudang?->tipe_gudang ?? '-'),
            'Tanggal Kunjungan: '.$booking->tanggal_kunjungan->format('d-m-Y'),
            'Jam: '.substr($booking->jam_mulai, 0, 5).' - '.substr($booking->jam_selesai, 0, 5).' WIB',
            'Status Pengajuan: '.$booking->status,
            '',
            'Bukti pengajuan ini bukan persetujuan Admin.',
            'Status pending berarti menunggu pemeriksaan Admin.',
            'Gudang baru di-keep setelah pengajuan disetujui Admin.',
        ];

        $stream = "BT /F1 12 Tf 50 790 Td 24 TL\n";
        foreach ($lines as $line) {
            $encoded = iconv('UTF-8', 'Windows-1252//TRANSLIT', $line);
            $escaped = str_replace(['\\', '(', ')', "\r", "\n"], ['\\\\', '\\(', '\\)', ' ', ' '], $encoded === false ? '?' : $encoded);
            $stream .= '('.$escaped.") Tj T*\n";
        }
        $stream .= 'ET';

        $objects = [
            '<< /Type /Catalog /Pages 2 0 R >>',
            '<< /Type /Pages /Kids [3 0 R] /Count 1 >>',
            '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 595 842] /Resources << /Font << /F1 4 0 R >> >> /Contents 5 0 R >>',
            '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica /Encoding /WinAnsiEncoding >>',
            '<< /Length '.strlen($stream).">>\nstream\n".$stream."\nendstream",
        ];
        $pdf = "%PDF-1.4\n";
        $offsets = [0];
        foreach ($objects as $index => $object) {
            $offsets[] = strlen($pdf);
            $pdf .= ($index + 1)." 0 obj\n".$object."\nendobj\n";
        }
        $xrefOffset = strlen($pdf);
        $pdf .= "xref\n0 6\n0000000000 65535 f \n";
        foreach (array_slice($offsets, 1) as $offset) {
            $pdf .= sprintf("%010d 00000 n \n", $offset);
        }

        return $pdf."trailer\n<< /Size 6 /Root 1 0 R >>\nstartxref\n".$xrefOffset."\n%%EOF\n";
    }
}
