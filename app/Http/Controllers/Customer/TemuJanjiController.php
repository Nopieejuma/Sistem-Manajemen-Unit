<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Gudang;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class TemuJanjiController extends Controller
{
    public function create(Request $request, string $group): View|RedirectResponse
    {
        $attributes = Gudang::groupingAttributesFromIdentifier($group);

        abort_unless($attributes, 404);

        $validator = Validator::make($request->only('unit'), [
            'unit' => ['required', 'uuid'],
        ]);

        if ($validator->fails()) {
            return to_route('customer.gudang.show', [
                'group' => $group,
                'unit_error' => $request->filled('unit') ? 'invalid' : 'required',
            ]);
        }

        $warehouse = Gudang::query()->sameGroup($attributes)->findOrFail($validator->validated()['unit']);

        if ($warehouse->status !== 'tersedia') {
            return to_route('customer.gudang.show', [
                'group' => $group,
                'unit' => $warehouse->id,
                'unit_error' => 'unavailable',
            ]);
        }

        $timeSlots = DB::table('jadwal')
            ->where('status_aktif', true)
            ->orderBy('jam_mulai')
            ->get(['id', 'jam_mulai', 'jam_selesai'])
            ->map(fn (object $schedule): array => [
                'id' => $schedule->id,
                'start' => substr($schedule->jam_mulai, 0, 5),
                'end' => substr($schedule->jam_selesai, 0, 5),
                'is_full' => false,
            ])->all();

        return view('customer.temu-janji.create', [
            'warehouse' => $warehouse,
            'detailUrl' => route('customer.gudang.show', ['group' => $group, 'unit' => $warehouse->id]),
            'today' => today(config('app.timezone'))->toDateString(),
            'timeSlots' => $timeSlots,
            'storeUrl' => route('customer.temu-janji.store', ['group' => $group]),
        ]);
    }

    public function store(Request $request, string $group): JsonResponse
    {
        $attributes = Gudang::groupingAttributesFromIdentifier($group);
        abort_unless($attributes, 404);

        $data = $request->validate([
            'gudang_id' => ['required', 'uuid'],
            'jadwal_id' => ['required', 'uuid'],
            'nama_perusahaan' => ['required', 'string', 'max:150'],
            'nama_pic' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:150'],
            'no_telepon' => ['required', 'string', 'max:30'],
            'tanggal_kunjungan' => ['required', 'date_format:Y-m-d', 'after_or_equal:today'],
            'catatan' => ['nullable', 'string', 'max:2000'],
        ], [
            'required' => ':attribute wajib diisi.',
            'uuid' => 'Pilihan :attribute tidak valid.',
            'email.email' => 'Format email tidak valid.',
            'tanggal_kunjungan.date_format' => 'Tanggal kunjungan tidak valid.',
            'tanggal_kunjungan.after_or_equal' => 'Tanggal kunjungan tidak boleh sebelum hari ini.',
            'max' => ':attribute maksimal :max karakter.',
        ], [
            'gudang_id' => 'unit gudang',
            'jadwal_id' => 'slot waktu',
            'nama_perusahaan' => 'Nama perusahaan',
            'nama_pic' => 'Nama PIC',
            'no_telepon' => 'Nomor telepon',
            'tanggal_kunjungan' => 'Tanggal kunjungan',
        ]);

        try {
            $bookingCode = DB::transaction(function () use ($data, $attributes): string {
                $warehouse = Gudang::query()->sameGroup($attributes)->lockForUpdate()->find($data['gudang_id']);

                if (! $warehouse || $warehouse->status !== 'tersedia') {
                    throw ValidationException::withMessages([
                        'gudang_id' => 'Unit gudang tidak tersedia atau pilihan tidak valid. Silakan pilih unit lain.',
                    ]);
                }

                $schedule = DB::table('jadwal')->where('id', $data['jadwal_id'])
                    ->where('status_aktif', true)->lockForUpdate()->first();

                if (! $schedule) {
                    throw ValidationException::withMessages([
                        'jadwal_id' => 'Slot waktu tidak tersedia. Silakan muat ulang halaman dan pilih jadwal aktif.',
                    ]);
                }

                $bookingCode = 'BK-'.strtoupper(bin2hex(random_bytes(12)));
                $timestamp = now();

                DB::table('temu_janji')->insert([
                    'id' => (string) Str::uuid(),
                    'kode_booking' => $bookingCode,
                    'gudang_id' => $warehouse->id,
                    'nama_perusahaan' => $data['nama_perusahaan'],
                    'nama_pic' => $data['nama_pic'],
                    'email' => $data['email'],
                    'no_telepon' => $data['no_telepon'],
                    'tanggal_kunjungan' => $data['tanggal_kunjungan'],
                    'jam_mulai' => $schedule->jam_mulai,
                    'jam_selesai' => $schedule->jam_selesai,
                    'catatan' => $data['catatan'] ?? null,
                    'status' => 'pending',
                    'created_at' => $timestamp,
                    'updated_at' => $timestamp,
                ]);

                return $bookingCode;
            });
        } catch (QueryException $exception) {
            report($exception);

            return response()->json([
                'message' => 'Permohonan belum dapat disimpan. Silakan coba kembali beberapa saat lagi.',
            ], 503);
        }

        return response()->json(['kode_booking' => $bookingCode, 'status' => 'pending'], 201);
    }
}
