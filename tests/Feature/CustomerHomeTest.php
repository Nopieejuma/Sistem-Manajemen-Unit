<?php

namespace Tests\Feature;

use App\Models\Gudang;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Tests\TestCase;

class CustomerHomeTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->assertSame('sqlite', DB::connection()->getDriverName());
        $this->assertSame(':memory:', DB::connection()->getDatabaseName());

        Schema::create('gudang', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            foreach (['unit_code', 'blok', 'tipe_gudang', 'status'] as $column) {
                $table->string($column)->nullable();
            }
            foreach (['luas_kavling', 'total_luas_bangunan', 'grand_total', 'harga_sewa'] as $column) {
                $table->decimal($column, 14, 2)->nullable();
            }
            $table->json('foto')->nullable();
            $table->json('fasilitas')->nullable();
        });
    }

    public function test_customer_home_renders_the_landing_page(): void
    {
        $this->createWarehouse('A5');

        $response = $this->get('/');

        $response->assertOk();
        $response->assertViewIs('customer.home');
        $response->assertSee('WAREHOUSE.BOOK');
        $response->assertSee('Gudang Tersedia');
        $response->assertSee('Gudang A5');
    }

    public function test_statistics_count_units_instead_of_catalog_groups(): void
    {
        $this->createWarehouse('A1');
        $this->createWarehouse('A2');
        $this->createWarehouse('B1', ['blok' => 'B', 'status' => 'sedang di-keep']);
        $this->createWarehouse('C1', ['blok' => 'C', 'status' => 'tidak tersedia']);
        $this->createWarehouse('D1', ['blok' => 'D', 'status' => null]);

        $response = $this->get('/');

        $response->assertViewHas('totalUnits', 5);
        $response->assertViewHas('availableUnits', 2);
        $response->assertViewHas('warehouses', fn ($groups): bool => $groups->count() === 1 && $groups->first()['unitCount'] === 2);
        $response->assertSee('Total Unit Gudang');
        $response->assertSee('Unit Gudang Tersedia');
        $response->assertSee('± 200 Ha');
    }

    public function test_recommendations_use_available_units_and_limit_after_grouping(): void
    {
        $this->createWarehouse('A0', ['status' => 'tidak tersedia']);
        $this->createWarehouse('A1');
        $this->createWarehouse('A2');
        $this->createWarehouse('A3');
        $this->createWarehouse('B1', ['blok' => 'B']);
        $this->createWarehouse('C1', ['blok' => 'C']);
        $this->createWarehouse('D1', ['blok' => 'D']);

        $response = $this->get('/');

        $response->assertViewHas('warehouses', function ($groups): bool {
            return $groups->count() === 3
                && $groups->pluck('block')->all() === ['Blok A', 'Blok B', 'Blok C']
                && $groups->pluck('unitCount')->all() === [3, 1, 1];
        });
        $response->assertDontSee('Gudang A0');
        $response->assertDontSee('Gudang D1');
    }

    public function test_recommendation_links_resolve_to_the_existing_catalog_group(): void
    {
        $warehouse = $this->createWarehouse('A5');
        $this->createWarehouse('A6');
        $url = route('customer.gudang.show', ['group' => $warehouse->groupIdentifier()]);

        $this->get('/')->assertSee('href="'.$url.'"', false);
        $this->get($url)->assertOk()->assertSee('data-unit-id="'.$warehouse->id.'"', false);
    }

    public function test_recommendations_use_database_photos_and_honest_placeholder(): void
    {
        $this->createWarehouse('A1');
        $this->createWarehouse('A2', ['foto' => json_encode(['https://example.com/warehouse.jpg'])]);
        $this->createWarehouse('B1', ['blok' => 'B']);

        $response = $this->get('/');

        $response->assertViewHas('warehouses', fn ($groups): bool => $groups[0]['image'] === 'https://example.com/warehouse.jpg'
            && $groups[1]['image'] === null);
        $response->assertSee('src="https://example.com/warehouse.jpg"', false);
        $response->assertSee('Harga belum tersedia');
        $response->assertSee('Foto belum tersedia');
    }

    public function test_empty_warehouse_table_renders_zero_statistics_and_no_recommendations(): void
    {
        $response = $this->get('/');

        $response->assertOk();
        $response->assertViewHas('totalUnits', 0);
        $response->assertViewHas('availableUnits', 0);
        $response->assertViewHas('warehouses', fn ($groups): bool => $groups->isEmpty());
        $response->assertSee('Belum ada unit gudang tersedia.');
        $response->assertSee('± 200 Ha');
    }

    public function test_no_available_units_renders_no_recommendations(): void
    {
        $this->createWarehouse('A1', ['status' => 'tidak tersedia']);

        $response = $this->get('/');

        $response->assertViewHas('totalUnits', 1);
        $response->assertViewHas('availableUnits', 0);
        $response->assertSee('Belum ada unit gudang tersedia.');
    }

    public function test_home_has_catalog_navigation_without_a_search_form(): void
    {
        $response = $this->get('/');

        $response->assertDontSee('type="search"', false)->assertDontSee('warehouse-search');
        $response->assertSee('Lihat Daftar Gudang')->assertSee('href="'.route('customer.gudang.index').'"', false);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function createWarehouse(string $code, array $attributes = []): Gudang
    {
        $id = (string) Str::uuid();
        DB::table('gudang')->insert(array_replace([
            'id' => $id,
            'unit_code' => $code,
            'blok' => 'A',
            'tipe_gudang' => 'Versa',
            'luas_kavling' => 7150,
            'total_luas_bangunan' => 4312,
            'grand_total' => 4324,
            'harga_sewa' => 0,
            'status' => 'tersedia',
            'foto' => json_encode([]),
            'fasilitas' => json_encode([]),
        ], $attributes));

        return Gudang::query()->findOrFail($id);
    }
}
