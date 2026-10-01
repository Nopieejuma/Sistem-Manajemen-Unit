<?php

namespace Tests\Feature;

use App\Models\Gudang;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class CustomerGudangIndexTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('gudang', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('unit_code');
            $table->string('blok')->nullable();
            $table->string('tipe_gudang')->nullable();
            $table->decimal('luas_kavling', 12, 2)->nullable();
            $table->decimal('total_luas_bangunan', 12, 2)->nullable();
            $table->decimal('grand_total', 12, 2)->nullable();
            $table->json('foto')->nullable();
            $table->decimal('harga_sewa', 14, 2)->nullable();
            $table->string('status')->nullable();
            $table->json('fasilitas')->nullable();
        });

        foreach (range(1, 13) as $number) {
            DB::table('gudang')->insert([
                'id' => sprintf('00000000-0000-0000-0000-%012d', $number),
                'unit_code' => $number === 1 ? 'A5' : "B{$number}",
                'blok' => $number === 1 ? 'A' : 'B',
                'tipe_gudang' => $number === 1 ? 'Versa' : 'Heksa',
                'luas_kavling' => 7150,
                'total_luas_bangunan' => 4312,
                'grand_total' => $number === 1 ? 4324 : 5000,
                'foto' => json_encode([]),
                'harga_sewa' => 0,
                'status' => 'tersedia',
                'fasilitas' => json_encode([]),
            ]);
        }
    }

    public function test_customer_can_search_and_filter_the_warehouse_list(): void
    {
        $response = $this->get('/gudang?search=A5&blok=A&tipe_gudang=Versa&status=tersedia');

        $response->assertOk();
        $response->assertViewIs('customer.gudang.index');
        $response->assertSee('Gudang A5');
        $response->assertSee('4.324 m²');
        $response->assertDontSee('Gudang B2');
    }

    public function test_customer_warehouse_list_groups_units_with_identical_physical_specs(): void
    {
        $response = $this->get('/gudang');
        $warehouse = Gudang::query()->where('unit_code', 'A5')->firstOrFail();

        $response->assertOk();
        $response->assertSee('2 kelompok gudang ditemukan');
        $response->assertSee('Heksa · 12 unit');
        $response->assertSee(route('customer.gudang.show', ['group' => $warehouse->groupIdentifier()]), false);
    }

    public function test_customer_can_view_a_group_detail_and_select_an_original_unit(): void
    {
        $warehouse = Gudang::query()->where('unit_code', 'A5')->firstOrFail();

        $response = $this->get(route('customer.gudang.show', [
            'group' => $warehouse->groupIdentifier(),
            'unit' => $warehouse->id,
        ]));

        $response->assertOk();
        $response->assertViewIs('customer.gudang.show');
        $response->assertSee('PILIH UNIT GUDANG');
        $response->assertSee('data-unit-id="'.$warehouse->id.'"', false);
    }

    public function test_customer_group_detail_returns_not_found_for_an_invalid_identifier(): void
    {
        $this->get('/gudang/invalid-group')->assertNotFound();
    }
}
