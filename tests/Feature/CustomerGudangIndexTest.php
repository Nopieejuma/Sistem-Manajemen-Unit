<?php

namespace Tests\Feature;

use App\Models\Gudang;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\TestWith;
use Tests\TestCase;

class CustomerGudangIndexTest extends TestCase
{
    #[TestWith(['A10'])]
    #[TestWith(['gudang A10'])]
    #[TestWith(['GUDANG   a10'])]
    #[TestWith(['blok A'])]
    #[TestWith(['Gudang Blok A'])]
    public function test_search_accepts_display_names_and_unit_codes(string $search): void
    {
        DB::table('gudang')->where('unit_code', 'A5')->update(['unit_code' => 'A10', 'status' => 'tidak tersedia']);

        $this->get('/gudang?'.http_build_query(['search' => $search]))
            ->assertOk()->assertSee('Gudang A10')->assertSee('Tidak Tersedia')
            ->assertViewHas('warehouseGroups', fn ($groups): bool => $groups->count() === 1);
        $this->get('/gudang?'.http_build_query(['search' => $search, 'status' => 'tersedia']))
            ->assertSee('Gudang tidak ditemukan');
    }

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

    public function test_array_filter_input_is_rejected_instead_of_causing_server_error(): void
    {
        $this->getJson('/gudang?search[]=A5')->assertUnprocessable()->assertJsonValidationErrors('search');
        $this->getJson('/gudang?tipe_gudang[]=Versa')->assertUnprocessable()->assertJsonValidationErrors('tipe_gudang');
    }

    public function test_nested_group_values_cannot_be_used_as_database_bindings(): void
    {
        $warehouse = Gudang::query()->where('unit_code', 'A5')->firstOrFail();
        $attributes = Gudang::groupingAttributesFromIdentifier($warehouse->groupIdentifier());
        $attributes['blok'] = ['A'];
        $identifier = rtrim(strtr(base64_encode(json_encode($attributes)), '+/', '-_'), '=');

        $this->get('/gudang/'.$identifier)->assertNotFound();
    }

    public function test_search_is_case_insensitive_and_sql_input_does_not_bypass_filters(): void
    {
        $this->get('/gudang?search=a5')->assertViewHas('warehouseGroups', fn ($groups): bool => $groups->count() === 1);
        $this->get('/gudang?search='.urlencode("' OR 1=1 --"))
            ->assertViewHas('warehouseGroups', fn ($groups): bool => $groups->isEmpty());
        $this->get('/gudang?tipe_gudang=unknown')->assertOk()
            ->assertViewHas('warehouseGroups', fn ($groups): bool => $groups->isEmpty());
    }

    public function test_unavailable_unit_cannot_offer_a_booking_action(): void
    {
        $warehouse = Gudang::query()->where('unit_code', 'A5')->firstOrFail();
        DB::table('gudang')->where('id', $warehouse->id)->update(['status' => 'sedang di-keep']);

        $this->get(route('customer.gudang.show', ['group' => $warehouse->groupIdentifier(), 'unit' => $warehouse->id]))
            ->assertOk()->assertSee('UNIT TIDAK TERSEDIA')->assertDontSee('KEEP GUDANG &amp; BUAT TEMU JANJI', false);
    }

    public function test_database_content_is_escaped_and_unsafe_photo_urls_are_not_rendered(): void
    {
        DB::table('gudang')->where('unit_code', 'A5')->update([
            'unit_code' => '<script>alert(1)</script>',
            'foto' => json_encode(['javascript:alert(1)', 'http://example.com/unsafe.jpg']),
            'fasilitas' => json_encode(['<script>alert(2)</script>']),
        ]);

        $this->get('/gudang')->assertOk()->assertSee('&lt;script&gt;', false)
            ->assertDontSee('<script>alert', false)->assertDontSee('javascript:alert', false)
            ->assertDontSee('http://example.com/unsafe.jpg', false);
    }

    public function test_malformed_unit_query_is_rejected(): void
    {
        $warehouse = Gudang::query()->where('unit_code', 'A5')->firstOrFail();
        $url = route('customer.gudang.show', ['group' => $warehouse->groupIdentifier()]);

        $this->getJson($url.'?unit[]=invalid')->assertUnprocessable()->assertJsonValidationErrors('unit');
    }

    public function test_group_identifier_rejects_invalid_numeric_values(): void
    {
        $warehouse = Gudang::query()->where('unit_code', 'A5')->firstOrFail();
        $attributes = Gudang::groupingAttributesFromIdentifier($warehouse->groupIdentifier());
        $attributes['harga_sewa'] = 'not-a-number';
        $identifier = rtrim(strtr(base64_encode(json_encode($attributes)), '+/', '-_'), '=');

        $this->assertNull(Gudang::groupingAttributesFromIdentifier($identifier));
        $this->get('/gudang/'.$identifier)->assertNotFound();
    }

    public function test_navigation_from_detail_links_back_to_booking_instructions(): void
    {
        $warehouse = Gudang::query()->where('unit_code', 'A5')->firstOrFail();

        $this->get(route('customer.gudang.show', ['group' => $warehouse->groupIdentifier()]))
            ->assertSee('href="'.route('customer.home').'#cara-pemesanan"', false);
    }

    #[TestWith(['tersedia', 'Tersedia', true])]
    #[TestWith(['sedang di-keep', 'Sedang Di-keep', false])]
    #[TestWith(['tidak tersedia', 'Tidak Tersedia', false])]
    #[TestWith(['digunakan', 'Tidak Tersedia', false])]
    public function test_search_finds_each_status_and_detail_respects_booking_availability(string $status, string $label, bool $canBook): void
    {
        DB::table('gudang')->where('unit_code', 'A5')->update(['status' => $status]);
        $warehouse = Gudang::query()->where('unit_code', 'A5')->firstOrFail();
        $detail = route('customer.gudang.show', ['group' => $warehouse->groupIdentifier(), 'unit' => $warehouse->id]);

        $this->get('/gudang?search=A5')->assertOk()->assertSee('Gudang A5')->assertSee($label)
            ->assertSee(route('customer.gudang.show', ['group' => $warehouse->groupIdentifier()]), false);
        $response = $this->get($detail)->assertOk()->assertSee($label);
        if ($canBook) {
            $response->assertSee('KEEP GUDANG &amp; BUAT TEMU JANJI', false);
        } else {
            $response->assertSee('UNIT TIDAK TERSEDIA')->assertDontSee('KEEP GUDANG &amp; BUAT TEMU JANJI', false);
        }
        $this->assertDatabaseHas('gudang', ['id' => $warehouse->id, 'status' => $status]);
    }

    public function test_mixed_statuses_stay_in_one_physical_group_and_are_all_visible(): void
    {
        DB::table('gudang')->where('unit_code', 'B2')->update(['status' => 'sedang di-keep']);
        DB::table('gudang')->where('unit_code', 'B3')->update(['status' => 'tidak tersedia']);
        DB::table('gudang')->where('unit_code', 'B4')->update(['status' => 'digunakan']);

        $response = $this->get('/gudang?search=B');

        $response->assertViewHas('warehouseGroups', fn ($groups): bool => $groups->count() === 1 && $groups->first()['unitCount'] === 12);
        $response->assertSee('Tersedia · 9 unit')->assertSee('Sedang Di-keep · 1 unit')->assertSee('Tidak Tersedia · 2 unit');
    }

    #[TestWith(['', 12])]
    #[TestWith(['tersedia', 9])]
    #[TestWith(['sedang di-keep', 1])]
    #[TestWith(['tidak tersedia', 2])]
    public function test_search_and_explicit_status_filter_combine_without_hiding_other_statuses_by_default(string $status, int $count): void
    {
        DB::table('gudang')->where('unit_code', 'B2')->update(['status' => 'sedang di-keep']);
        DB::table('gudang')->where('unit_code', 'B3')->update(['status' => 'tidak tersedia']);
        DB::table('gudang')->where('unit_code', 'B4')->update(['status' => 'digunakan']);

        $response = $this->get('/gudang?'.http_build_query(['search' => 'B', 'status' => $status]));

        $response->assertViewHas('warehouseGroups', fn ($groups): bool => $groups->count() === 1 && $groups->first()['unitCount'] === $count);
        $response->assertSee('name="status" value="'.$status.'"', false);
    }

    public function test_search_with_mismatched_status_has_clear_empty_state(): void
    {
        DB::table('gudang')->where('unit_code', 'A5')->update(['status' => 'tidak tersedia']);

        $this->get('/gudang?search=A5&status=tersedia')->assertOk()->assertSee('Gudang tidak ditemukan')
            ->assertSee('Coba ubah kata kunci atau pilihan filter Anda.');
        $this->get('/gudang?search=NONEXISTENT')->assertOk()->assertSee('Gudang tidak ditemukan');
    }
}
