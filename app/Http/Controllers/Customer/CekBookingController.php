<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class CekBookingController extends Controller
{
    public function index(): Response
    {
        return response()->view('customer.cek-booking')->header('Cache-Control', 'no-store, private');
    }

    public function store(Request $request): Response
    {
        $validator = Validator::make($request->post(), [
            'kode_booking' => ['required', 'string', 'max:30'],
            'email' => ['required', 'string', 'email', 'max:150'],
        ], [
            'required' => ':attribute wajib diisi.',
            'string' => ':attribute harus berupa teks.',
            'max' => ':attribute maksimal :max karakter.',
            'email.email' => 'Format email tidak valid.',
        ], [
            'kode_booking' => 'Kode Booking',
            'email' => 'Email Customer',
        ]);

        if ($validator->fails()) {
            return response()->view('customer.cek-booking', [
                'validationErrors' => $validator->errors(),
            ], 422)->header('Cache-Control', 'no-store, private');
        }

        $data = $validator->validated();
        $booking = DB::table('temu_janji')
            ->join('gudang', 'gudang.id', '=', 'temu_janji.gudang_id')
            ->where('temu_janji.kode_booking', $data['kode_booking'])
            ->where('temu_janji.email', $data['email'])
            ->first([
                'temu_janji.kode_booking', 'gudang.unit_code', 'temu_janji.tanggal_kunjungan',
                'temu_janji.jam_mulai', 'temu_janji.jam_selesai', 'temu_janji.status',
            ]);

        if (! $booking) {
            return response()->view('customer.cek-booking', [
                'notice' => 'Data temu janji tidak ditemukan. Periksa kembali kode booking dan email Anda.',
            ])->header('Cache-Control', 'no-store, private');
        }

        [$statusLabel, $statusMessage] = match ($booking->status) {
            'pending' => ['Menunggu Persetujuan', 'Permohonan Anda sedang menunggu pemeriksaan admin.'],
            'approved' => ['Disetujui', 'Permohonan Anda telah disetujui. Silakan mengikuti jadwal kunjungan yang telah ditentukan.'],
            'rejected' => ['Ditolak', 'Permohonan Anda belum dapat disetujui. Silakan menghubungi admin untuk informasi lebih lanjut.'],
            'completed' => ['Selesai', 'Kunjungan Anda telah selesai.'],
            'canceled' => ['Dibatalkan', 'Permohonan temu janji ini telah dibatalkan.'],
            default => [Str::headline($booking->status ?: 'Status belum tersedia'), 'Silakan menghubungi admin untuk informasi lebih lanjut.'],
        };

        return response()->view('customer.cek-booking', compact('booking', 'statusLabel', 'statusMessage'))
            ->header('Cache-Control', 'no-store, private');
    }
}
