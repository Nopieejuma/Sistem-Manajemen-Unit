<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Gudang;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class GudangController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim($request->string('search')->toString());
        $searchPattern = '%'.mb_strtolower($search).'%';
        $blok = $request->string('blok')->toString();
        $tipeGudang = $request->string('tipe_gudang')->toString();
        $status = $request->string('status')->toString();

        $warehouses = Gudang::query()
            ->select([
                'id',
                'unit_code',
                'blok',
                'tipe_gudang',
                'luas_kavling',
                'grand_total',
                'total_luas_bangunan',
                'harga_sewa',
                'status',
                'foto',
                'fasilitas',
            ])
            ->when($search !== '', function (Builder $query) use ($searchPattern): void {
                $query->where(function (Builder $query) use ($searchPattern): void {
                    $query
                        ->whereRaw('LOWER(unit_code) LIKE ?', [$searchPattern])
                        ->orWhereRaw('LOWER(blok) LIKE ?', [$searchPattern])
                        ->orWhereRaw('LOWER(CAST(tipe_gudang AS TEXT)) LIKE ?', [$searchPattern]);
                });
            })
            ->when($blok !== '', fn (Builder $query): Builder => $query->where('blok', $blok))
            ->when($tipeGudang !== '', fn (Builder $query): Builder => $query->where('tipe_gudang', $tipeGudang))
            ->when($status !== '', fn (Builder $query): Builder => $query->where('status', $status))
            ->orderBy('unit_code')
            ->get();

        $warehouseGroups = $warehouses
            ->groupBy(fn (Gudang $warehouse): string => $warehouse->groupKey())
            ->map(function (Collection $units): array {
                $units = $units->sortBy('unit_code')->values();
                $statuses = $units->pluck('status')->filter()->unique()->values();
                $facilitySets = $units
                    ->map(fn (Gudang $warehouse): string => json_encode($warehouse->fasilitas ?? []))
                    ->unique();

                return [
                    'warehouse' => $units->first(),
                    'unitCount' => $units->count(),
                    'availableUnitCount' => $units->where('status', 'tersedia')->count(),
                    'status' => $statuses->count() === 1
                        ? $statuses->first()
                        : ($units->where('status', 'tersedia')->isNotEmpty() ? 'tersedia' : $statuses->first()),
                    'facilities' => $facilitySets->count() === 1
                        ? collect($units->first()->fasilitas ?? [])
                        : collect(),
                ];
            })
            ->values();

        return view('customer.gudang.index', [
            'warehouseGroups' => $warehouseGroups,
            'search' => $search,
            'filters' => [
                'blok' => $blok,
                'tipe_gudang' => $tipeGudang,
                'status' => $status,
            ],
            'blokOptions' => Gudang::query()->whereNotNull('blok')->distinct()->orderBy('blok')->pluck('blok'),
            'tipeGudangOptions' => Gudang::query()->whereNotNull('tipe_gudang')->distinct()->orderBy('tipe_gudang')->pluck('tipe_gudang'),
        ]);
    }

    public function show(Request $request, string $group): View
    {
        $groupingAttributes = Gudang::groupingAttributesFromIdentifier($group);

        abort_unless($groupingAttributes, 404);

        $units = Gudang::query()
            ->select([
                'id',
                'unit_code',
                'blok',
                'tipe_gudang',
                'luas_kavling',
                'total_luas_bangunan',
                'grand_total',
                'harga_sewa',
                'status',
                'foto',
                'fasilitas',
            ])
            ->sameGroup($groupingAttributes)
            ->orderBy('unit_code')
            ->get();

        abort_if($units->isEmpty(), 404);

        $requestedUnitId = $request->string('unit')->toString();
        $selectedUnit = $units->firstWhere('id', $requestedUnitId)
            ?? $units->firstWhere('status', 'tersedia')
            ?? $units->first();

        return view('customer.gudang.show', [
            'groupIdentifier' => $group,
            'units' => $units,
            'selectedUnit' => $selectedUnit,
            'hasSelectedUnit' => $requestedUnitId !== '' && $units->contains('id', $requestedUnitId),
            'unitSelectionError' => match ($request->query('unit_error')) {
                'required' => 'Pilih unit gudang terlebih dahulu sebelum membuat temu janji.',
                'invalid' => 'Pilihan unit gudang tidak valid. Silakan pilih kembali.',
                'unavailable' => 'Unit gudang ini tidak tersedia. Silakan pilih unit lain.',
                default => null,
            },
            'photoUrls' => $selectedUnit->photoUrls(),
        ]);
    }
}
