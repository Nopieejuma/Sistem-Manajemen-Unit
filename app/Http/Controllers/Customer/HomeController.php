<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Gudang;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function index(): View
    {
        $totalUnits = Gudang::query()->count();
        $availableUnits = Gudang::query()->where('status', 'tersedia')->count();

        $warehouses = Gudang::query()
            ->select([
                'id', 'unit_code', 'blok', 'tipe_gudang', 'luas_kavling',
                'total_luas_bangunan', 'grand_total', 'harga_sewa', 'foto', 'fasilitas',
            ])
            ->where('status', 'tersedia')
            ->orderBy('unit_code')
            ->get()
            ->groupBy(fn (Gudang $warehouse): string => $warehouse->groupKey())
            ->take(3)
            ->map(function (Collection $units): array {
                $warehouse = $units->first();
                $photoUrl = $units->map(fn (Gudang $unit): ?string => $unit->firstPhotoUrl())->filter()->first();
                $facilitySets = $units->map(fn (Gudang $unit): string => json_encode($unit->fasilitas ?? []))->unique();
                $area = $warehouse->grand_total ?: $warehouse->total_luas_bangunan;

                return [
                    'name' => $units->count() === 1 ? "Gudang {$warehouse->unit_code}" : "Gudang Blok {$warehouse->blok}",
                    'block' => "Blok {$warehouse->blok}",
                    'unitCount' => $units->count(),
                    'area' => $area ? number_format((float) $area, 0, ',', '.').' m²' : 'Luas belum tersedia',
                    'price' => (float) $warehouse->harga_sewa > 0
                        ? 'Rp'.number_format((float) $warehouse->harga_sewa, 0, ',', '.')
                        : null,
                    'facilities' => $facilitySets->count() === 1 ? ($warehouse->fasilitas ?? []) : [],
                    'image' => $photoUrl ?? asset('images/warehouses/warehouse-a5.png'),
                    'detailUrl' => route('customer.gudang.show', ['group' => $warehouse->groupIdentifier()]),
                ];
            })
            ->values();

        return view('customer.home', compact('warehouses', 'totalUnits', 'availableUnits'));
    }
}
