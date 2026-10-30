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
        $request->validate([
            'search' => ['nullable', 'string', 'max:150'],
            'blok' => ['nullable', 'string', 'max:10'],
            'tipe_gudang' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', 'string', 'max:50'],
        ]);

        $search = trim($request->string('search')->toString());
        $normalizedSearch = preg_replace('/\s+/u', ' ', $search);
        $searchByBlock = preg_match('/^(?:gudang\s+)?blok\s+/iu', $normalizedSearch) === 1;
        $normalizedSearch = preg_replace('/^(?:gudang\s+(?:blok\s+)?|blok\s+)/iu', '', $normalizedSearch);
        $searchPattern = '%'.mb_strtolower($normalizedSearch).'%';
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
            ->when($search !== '', function (Builder $query) use ($searchPattern, $searchByBlock): void {
                $query->where(function (Builder $query) use ($searchPattern, $searchByBlock): void {
                    if ($searchByBlock) {
                        $query->whereRaw('LOWER(blok) LIKE ?', [$searchPattern]);

                        return;
                    }

                    $query
                        ->whereRaw('LOWER(unit_code) LIKE ?', [$searchPattern])
                        ->orWhereRaw('LOWER(blok) LIKE ?', [$searchPattern])
                        ->orWhereRaw('LOWER(CAST(tipe_gudang AS TEXT)) LIKE ?', [$searchPattern]);
                });
            })
            ->when($blok !== '', fn (Builder $query): Builder => $query->where('blok', $blok))
            ->when($tipeGudang !== '', fn (Builder $query): Builder => $query->whereRaw('CAST(tipe_gudang AS TEXT) = ?', [$tipeGudang]))
            ->when($status !== '', fn (Builder $query): Builder => $query->whereIn('status',
                $status === 'tidak tersedia' ? ['tidak tersedia', 'digunakan'] : [$status]))
            ->orderBy('unit_code')
            ->get();

        $warehouseGroups = $warehouses
            ->groupBy(fn (Gudang $warehouse): string => $warehouse->groupKey())
            ->map(function (Collection $units): array {
                $units = $units->sortBy('unit_code')->values();
                $statusCounts = $units->groupBy(fn (Gudang $unit): string => $unit->statusLabel())
                    ->map(fn (Collection $statusUnits): int => $statusUnits->count());
                $facilitySets = $units
                    ->map(fn (Gudang $warehouse): string => json_encode($warehouse->fasilitas ?? []))
                    ->unique();

                return [
                    'warehouse' => $units->first(),
                    'unitCount' => $units->count(),
                    'availableUnitCount' => $units->where('status', 'tersedia')->count(),
                    'statusCounts' => $statusCounts,
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
        $request->validate(['unit' => ['nullable', 'uuid']]);

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
            'groupIdentifier' => $selectedUnit->groupIdentifier(),
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
