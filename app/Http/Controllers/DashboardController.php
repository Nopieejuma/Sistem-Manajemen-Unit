<?php

namespace App\Http\Controllers;

use App\Models\Gudang;
use App\Models\TemuJanji;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Illuminate\View\View;

class DashboardController extends Controller
{
    private const array WarehouseStatuses = ['tersedia', 'sedang di-keep', 'tidak tersedia', 'digunakan'];

    private const array BookingStatuses = ['pending', 'approved', 'rejected', 'completed', 'canceled'];

    private const array ActiveBookingStatuses = ['pending', 'approved'];

    public function index(): View
    {
        $warehouseCounts = array_fill_keys(self::WarehouseStatuses, 0);
        $warehouseGroups = Gudang::query()
            ->select('status')
            ->selectRaw('COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');
        $totalWarehouses = 0;

        foreach ($warehouseGroups as $status => $total) {
            $totalWarehouses += (int) $total;

            if (array_key_exists($status, $warehouseCounts)) {
                $warehouseCounts[$status] = (int) $total;
            }
        }

        $warehousePercentages = [];

        foreach ($warehouseCounts as $status => $count) {
            $warehousePercentages[$status] = $totalWarehouses > 0
                ? round(($count / $totalWarehouses) * 100, 1)
                : 0.0;
        }

        $bookingCounts = array_fill_keys(self::BookingStatuses, 0);
        $bookingGroups = TemuJanji::query()
            ->select('status')
            ->selectRaw('COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        foreach ($bookingGroups as $status => $total) {
            if (array_key_exists($status, $bookingCounts)) {
                $bookingCounts[$status] = (int) $total;
            }
        }

        $jakartaToday = CarbonImmutable::now('Asia/Jakarta')->startOfDay();
        $today = $jakartaToday->toDateString();
        $weekStart = $jakartaToday->startOfWeek(Carbon::MONDAY)->toDateString();
        $weekEnd = $jakartaToday->endOfWeek(Carbon::SUNDAY)->toDateString();

        $activeAppointmentsToday = TemuJanji::query()
            ->whereIn('status', self::ActiveBookingStatuses)
            ->whereDate('tanggal_kunjungan', $today)
            ->count();
        $activeAppointmentsThisWeek = TemuJanji::query()
            ->whereIn('status', self::ActiveBookingStatuses)
            ->whereBetween('tanggal_kunjungan', [$weekStart, $weekEnd])
            ->count();

        $recentBookings = TemuJanji::query()
            ->with('gudang:id,unit_code,blok')
            ->orderByRaw('CASE WHEN created_at IS NULL THEN 1 ELSE 0 END')
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->limit(5)
            ->get([
                'id',
                'kode_booking',
                'gudang_id',
                'nama_perusahaan',
                'tanggal_kunjungan',
                'jam_mulai',
                'jam_selesai',
                'status',
                'created_at',
            ]);

        return view('dashboard', [
            'totalWarehouses' => $totalWarehouses,
            'warehouseCounts' => $warehouseCounts,
            'warehousePercentages' => $warehousePercentages,
            'warehouseChartLabels' => ['Tersedia', 'Sedang di-keep', 'Tidak tersedia', 'Digunakan'],
            'warehouseChartValues' => array_values($warehouseCounts),
            'bookingCounts' => $bookingCounts,
            'activeAppointmentsToday' => $activeAppointmentsToday,
            'activeAppointmentsThisWeek' => $activeAppointmentsThisWeek,
            'recentBookings' => $recentBookings,
        ]);
    }
}
