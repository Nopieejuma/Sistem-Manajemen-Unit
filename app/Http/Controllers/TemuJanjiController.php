<?php

namespace App\Http\Controllers;

use App\Models\Gudang;
use App\Models\TemuJanji;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class TemuJanjiController extends Controller
{
    private const array Statuses = ['pending', 'approved', 'rejected', 'completed', 'canceled'];

    private const array CapacityStatuses = ['pending', 'approved', 'completed'];

    /** @var array<string, array<int, string>> */
    private const array AllowedTransitions = [
        'approve' => ['pending'],
        'reject' => ['pending'],
        'cancel' => ['pending', 'approved'],
        'complete' => ['approved'],
    ];

    /** @var array<string, string> */
    private const array TargetStatuses = [
        'approve' => 'approved',
        'reject' => 'rejected',
        'cancel' => 'canceled',
        'complete' => 'completed',
    ];

    public function index(Request $request): View
    {
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:150'],
            'status' => ['nullable', Rule::in(self::Statuses)],
            'tanggal_kunjungan' => ['nullable', 'date_format:Y-m-d'],
        ]);

        $bookings = TemuJanji::query()
            ->with('gudang:id,unit_code,blok')
            ->when($filters['q'] ?? null, function (Builder $query, string $search): void {
                $query->where(function (Builder $query) use ($search): void {
                    $query->where('kode_booking', 'like', '%'.$search.'%')
                        ->orWhere('nama_perusahaan', 'like', '%'.$search.'%')
                        ->orWhere('nama_pic', 'like', '%'.$search.'%');
                });
            })
            ->when($filters['status'] ?? null, fn (Builder $query, string $status): Builder => $query->where('status', $status))
            ->when($filters['tanggal_kunjungan'] ?? null, fn (Builder $query, string $date): Builder => $query->whereDate('tanggal_kunjungan', $date))
            ->orderByDesc('tanggal_kunjungan')
            ->orderBy('jam_mulai')
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();

        return view('temu_janji.index', [
            'bookings' => $bookings,
            'statuses' => self::Statuses,
            'filters' => $filters,
        ]);
    }

    public function show(TemuJanji $temuJanji): View
    {
        $temuJanji->load('gudang:id,unit_code,blok,tipe_gudang');

        $schedule = DB::table('jadwal')
            ->where('jam_mulai', $temuJanji->jam_mulai)
            ->where('jam_selesai', $temuJanji->jam_selesai)
            ->where('status_aktif', true)
            ->orderBy('id')
            ->first(['kuota']);

        $usedCapacity = TemuJanji::query()
            ->whereDate('tanggal_kunjungan', $temuJanji->tanggal_kunjungan->toDateString())
            ->where('jam_mulai', $temuJanji->jam_mulai)
            ->where('jam_selesai', $temuJanji->jam_selesai)
            ->whereIn('status', self::CapacityStatuses)
            ->count();

        return view('temu_janji.show', [
            'booking' => $temuJanji,
            'usedCapacity' => $usedCapacity,
            'capacityLimit' => $schedule?->kuota,
            'allowedActions' => array_keys(array_filter(
                self::AllowedTransitions,
                fn (array $fromStatuses): bool => in_array($temuJanji->status, $fromStatuses, true),
            )),
        ]);
    }

    public function approve(TemuJanji $temuJanji): RedirectResponse
    {
        return $this->transition($temuJanji, 'approve');
    }

    public function reject(TemuJanji $temuJanji): RedirectResponse
    {
        return $this->transition($temuJanji, 'reject');
    }

    public function cancel(TemuJanji $temuJanji): RedirectResponse
    {
        return $this->transition($temuJanji, 'cancel');
    }

    public function complete(TemuJanji $temuJanji): RedirectResponse
    {
        return $this->transition($temuJanji, 'complete');
    }

    private function transition(TemuJanji $temuJanji, string $action): RedirectResponse
    {
        $targetStatus = self::TargetStatuses[$action];

        DB::transaction(function () use ($temuJanji, $action, $targetStatus): void {
            $booking = TemuJanji::query()
                ->whereKey($temuJanji->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if (! in_array($booking->status, self::AllowedTransitions[$action], true)) {
                throw ValidationException::withMessages([
                    'status' => 'Status booking telah berubah atau transisi ini tidak diizinkan.',
                ]);
            }

            $warehouse = Gudang::query()
                ->whereKey($booking->gudang_id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($targetStatus === 'approved') {
                if (! in_array($warehouse->status, ['tersedia', 'sedang di-keep'], true)) {
                    throw ValidationException::withMessages([
                        'gudang_id' => 'Gudang tidak dapat disetujui karena statusnya bukan tersedia atau sedang di-keep.',
                    ]);
                }

                if ($warehouse->status === 'tersedia') {
                    $warehouse->status = 'sedang di-keep';
                    $warehouse->save();
                }
            }

            if (in_array($targetStatus, ['rejected', 'canceled'], true)) {
                DB::table('jadwal')
                    ->where('jam_mulai', $booking->jam_mulai)
                    ->where('jam_selesai', $booking->jam_selesai)
                    ->orderBy('id')
                    ->lockForUpdate()
                    ->get(['id']);
            }

            if ($booking->status === 'approved' && in_array($targetStatus, ['canceled', 'completed'], true)) {
                $anotherApprovedBookingExists = TemuJanji::query()
                    ->where('gudang_id', $booking->gudang_id)
                    ->where('status', 'approved')
                    ->where('id', '!=', $booking->getKey())
                    ->exists();

                if (! $anotherApprovedBookingExists && $warehouse->status === 'sedang di-keep') {
                    $warehouse->status = 'tersedia';
                    $warehouse->save();
                }
            }

            $booking->status = $targetStatus;
            $booking->save();
        });

        return to_route('temu-janji.show', $temuJanji)->with('success', 'Status booking berhasil diperbarui.');
    }
}
