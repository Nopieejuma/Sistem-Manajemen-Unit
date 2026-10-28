<?php

namespace App\Http\Controllers;

use App\Models\Jadwal;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class JadwalController extends Controller
{
    private const array CapacityStatuses = ['pending', 'approved', 'completed'];

    private const int AdvisoryLockNamespace = 1296648013;

    private const int AdvisoryLockKey = 4;

    public function index(): View
    {
        $schedules = Jadwal::query()->orderBy('jam_mulai')->orderBy('id')->get();

        $usageBySlot = DB::table('temu_janji')
            ->select('tanggal_kunjungan', 'jam_mulai', 'jam_selesai')
            ->selectRaw('COUNT(*) AS used_capacity')
            ->whereIn('status', self::CapacityStatuses)
            ->groupBy('tanggal_kunjungan', 'jam_mulai', 'jam_selesai')
            ->orderByDesc('tanggal_kunjungan')
            ->get()
            ->groupBy(fn (object $usage): string => $this->slotKey($usage->jam_mulai, $usage->jam_selesai));

        $schedules->each(function (Jadwal $schedule) use ($usageBySlot): void {
            $schedule->setAttribute(
                'usage_by_date',
                $usageBySlot->get($this->slotKey($schedule->jam_mulai, $schedule->jam_selesai), collect()),
            );
        });

        return view('jadwal.index', ['schedules' => $schedules]);
    }

    public function create(): View
    {
        return view('jadwal.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validatedSchedule($request);

        DB::transaction(function () use ($data): void {
            $this->acquireScheduleWriteLock();

            $this->validateScheduleTimeOrder($data['jam_mulai'], $data['jam_selesai']);
            $this->validateSlotIsAvailable(
                $data['jam_mulai'],
                $data['jam_selesai'],
                (bool) $data['status_aktif'],
            );

            Jadwal::query()->create([
                'id' => (string) Str::uuid(),
                'jam_mulai' => $this->normalizeTime($data['jam_mulai']),
                'jam_selesai' => $this->normalizeTime($data['jam_selesai']),
                'kuota' => (int) $data['kuota'],
                'status_aktif' => (bool) $data['status_aktif'],
            ]);
        });

        return to_route('jadwal.index')->with('success', 'Jadwal berhasil ditambahkan.');
    }

    public function edit(Jadwal $jadwal): View
    {
        return view('jadwal.edit', [
            'schedule' => $jadwal,
            'hasBookingHistory' => $this->hasBookingHistory($jadwal->jam_mulai, $jadwal->jam_selesai),
        ]);
    }

    public function update(Request $request, Jadwal $jadwal): RedirectResponse
    {
        $data = $this->validatedSchedule($request, false);

        DB::transaction(function () use ($data, $jadwal): void {
            $this->acquireScheduleWriteLock();

            $schedule = Jadwal::query()->whereKey($jadwal->getKey())->lockForUpdate()->firstOrFail();
            $this->validateScheduleTimeOrder($data['jam_mulai'], $data['jam_selesai']);

            $newStart = $this->normalizeTime($data['jam_mulai']);
            $newEnd = $this->normalizeTime($data['jam_selesai']);
            $oldStart = $this->normalizeTime($schedule->jam_mulai);
            $oldEnd = $this->normalizeTime($schedule->jam_selesai);
            $timeHasChanged = $newStart !== $oldStart || $newEnd !== $oldEnd;

            if ($timeHasChanged && $this->hasBookingHistory($oldStart, $oldEnd)) {
                throw ValidationException::withMessages([
                    'jam_mulai' => 'Jam jadwal tidak dapat diubah karena sudah memiliki riwayat booking.',
                ]);
            }

            $this->validateSlotIsAvailable($newStart, $newEnd, $schedule->status_aktif, $schedule->id);

            $newQuota = (int) $data['kuota'];
            $highestDailyUsage = $this->highestDailyUsage($newStart, $newEnd);

            if ($newQuota < $highestDailyUsage) {
                throw ValidationException::withMessages([
                    'kuota' => 'Kuota tidak boleh lebih kecil dari pemakaian tertinggi pada satu tanggal ('.$highestDailyUsage.').',
                ]);
            }

            $schedule->update([
                'jam_mulai' => $newStart,
                'jam_selesai' => $newEnd,
                'kuota' => $newQuota,
            ]);
        });

        return to_route('jadwal.index')->with('success', 'Jadwal berhasil diperbarui.');
    }

    public function activate(Jadwal $jadwal): RedirectResponse
    {
        DB::transaction(function () use ($jadwal): void {
            $this->acquireScheduleWriteLock();

            $schedule = Jadwal::query()->whereKey($jadwal->getKey())->lockForUpdate()->firstOrFail();
            $this->validateSlotIsAvailable($schedule->jam_mulai, $schedule->jam_selesai, true, $schedule->id);
            $schedule->update(['status_aktif' => true]);
        });

        return to_route('jadwal.index')->with('success', 'Jadwal berhasil diaktifkan.');
    }

    public function deactivate(Jadwal $jadwal): RedirectResponse
    {
        DB::transaction(function () use ($jadwal): void {
            $schedule = Jadwal::query()->whereKey($jadwal->getKey())->lockForUpdate()->firstOrFail();
            $schedule->update(['status_aktif' => false]);
        });

        return to_route('jadwal.index')->with('success', 'Jadwal dinonaktifkan. Riwayat booking tetap tersimpan.');
    }

    /** @return array<string, mixed> */
    private function validatedSchedule(Request $request, bool $includeStatus = true): array
    {
        $rules = [
            'jam_mulai' => ['required', 'date_format:H:i'],
            'jam_selesai' => ['required', 'date_format:H:i'],
            'kuota' => ['required', 'integer', 'min:1', 'max:2147483647'],
        ];

        if ($includeStatus) {
            $rules['status_aktif'] = ['required', 'boolean'];
        }

        return $request->validate($rules, [
            'jam_mulai.required' => 'Jam mulai wajib diisi.',
            'jam_mulai.date_format' => 'Format jam mulai tidak valid.',
            'jam_selesai.required' => 'Jam selesai wajib diisi.',
            'jam_selesai.date_format' => 'Format jam selesai tidak valid.',
            'kuota.required' => 'Kuota wajib diisi.',
            'kuota.integer' => 'Kuota harus berupa bilangan bulat.',
            'kuota.min' => 'Kuota minimal 1.',
            'status_aktif.boolean' => 'Status aktif tidak valid.',
        ]);
    }

    private function validateScheduleTimeOrder(string $start, string $end): void
    {
        if ($end <= $start) {
            throw ValidationException::withMessages([
                'jam_selesai' => 'Jam selesai harus lebih besar dari jam mulai.',
            ]);
        }
    }

    private function validateSlotIsAvailable(string $start, string $end, bool $mustBeActive, ?string $exceptId = null): void
    {
        $start = $this->normalizeTime($start);
        $end = $this->normalizeTime($end);

        $duplicate = Jadwal::query()
            ->when($exceptId !== null, fn ($query) => $query->where('id', '!=', $exceptId))
            ->where('jam_mulai', $start)
            ->where('jam_selesai', $end)
            ->exists();

        if ($duplicate) {
            throw ValidationException::withMessages([
                'jam_mulai' => 'Pasangan jam ini sudah ada. Aktifkan atau edit slot lama; jadwal duplikat tidak dapat dibuat.',
            ]);
        }

        if (! $mustBeActive) {
            return;
        }

        $overlap = Jadwal::query()
            ->where('status_aktif', true)
            ->when($exceptId !== null, fn ($query) => $query->where('id', '!=', $exceptId))
            ->where('jam_mulai', '<', $end)
            ->where('jam_selesai', '>', $start)
            ->exists();

        if ($overlap) {
            throw ValidationException::withMessages([
                'jam_mulai' => 'Rentang waktu bentrok dengan jadwal aktif lainnya.',
            ]);
        }
    }

    private function hasBookingHistory(string $start, string $end): bool
    {
        return DB::table('temu_janji')
            ->where('jam_mulai', $this->normalizeTime($start))
            ->where('jam_selesai', $this->normalizeTime($end))
            ->exists();
    }

    private function highestDailyUsage(string $start, string $end): int
    {
        return (int) (DB::table('temu_janji')
            ->select('tanggal_kunjungan')
            ->selectRaw('COUNT(*) AS used_capacity')
            ->where('jam_mulai', $this->normalizeTime($start))
            ->where('jam_selesai', $this->normalizeTime($end))
            ->whereIn('status', self::CapacityStatuses)
            ->groupBy('tanggal_kunjungan')
            ->get()
            ->max('used_capacity') ?? 0);
    }

    private function acquireScheduleWriteLock(): void
    {
        if (DB::connection()->getDriverName() === 'pgsql') {
            DB::select('SELECT pg_advisory_xact_lock(?, ?)', [self::AdvisoryLockNamespace, self::AdvisoryLockKey]);
        }
    }

    private function normalizeTime(string $time): string
    {
        return strlen($time) === 5 ? $time.':00' : $time;
    }

    private function slotKey(string $start, string $end): string
    {
        return $this->normalizeTime($start).'|'.$this->normalizeTime($end);
    }
}
