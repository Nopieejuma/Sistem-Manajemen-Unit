<?php

namespace Tests\Feature;

use App\Models\Gudang;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\TestWith;
use Tests\TestCase;

class CustomerTemuJanjiTest extends TestCase
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
        Schema::create('jadwal', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->time('jam_mulai');
            $table->time('jam_selesai');
            $table->integer('kuota')->default(1);
            $table->boolean('status_aktif')->default(true);
        });
        Schema::create('temu_janji', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('kode_booking', 30)->unique();
            $table->foreignUuid('gudang_id')->constrained('gudang');
            $table->string('nama_perusahaan', 150);
            $table->string('nama_pic', 100);
            $table->string('email', 150);
            $table->string('no_telepon', 30);
            $table->date('tanggal_kunjungan');
            $table->time('jam_mulai');
            $table->time('jam_selesai');
            $table->text('catatan')->nullable();
            $table->string('status', 30)->default('pending');
            $table->timestampsTz();
        });
    }

    public function test_form_displays_the_selected_unit_without_changing_warehouse_data(): void
    {
        $this->freezeTime();
        $warehouse = $this->warehouse();
        $before = DB::table('gudang')->get()->toJson();
        $detailUrl = route('customer.gudang.show', ['group' => $warehouse->groupIdentifier(), 'unit' => $warehouse->id]);

        $response = $this->get(route('customer.temu-janji.create', ['group' => $warehouse->groupIdentifier(), 'unit' => $warehouse->id]));

        $response->assertOk()
            ->assertViewIs('customer.temu-janji.create')
            ->assertSee('Gudang C17')
            ->assertSee('Blok C')
            ->assertSee('4.400 m²')
            ->assertSee('Rp16.000.000 / bulan')
            ->assertSee('TERSEDIA')
            ->assertSee('https://example.com/unit-c17.jpg', false)
            ->assertSee('min="'.today()->toDateString().'"', false)
            ->assertSee('href="'.$detailUrl.'"', false)
            ->assertSee('Jadwal kunjungan belum tersedia.')
            ->assertSee('Pengajuan belum berarti gudang di-keep.')
            ->assertSee('type="submit" disabled', false)
            ->assertViewHas('timeSlots', []);

        $this->assertSame($before, DB::table('gudang')->get()->toJson());
    }

    public function test_detail_links_directly_to_the_form_with_the_explicitly_selected_unit(): void
    {
        $warehouse = $this->warehouse();
        $otherUnit = $this->warehouse(['id' => '00000000-0000-0000-0000-000000000002', 'unit_code' => 'C18']);

        $this->get(route('customer.gudang.show', ['group' => $warehouse->groupIdentifier(), 'unit' => $otherUnit->id]))
            ->assertOk()
            ->assertViewHas('hasSelectedUnit', true)
            ->assertSee(route('customer.temu-janji.create', ['group' => $warehouse->groupIdentifier(), 'unit' => $otherUnit->id]), false);

        $this->get(route('customer.temu-janji.create', ['group' => $warehouse->groupIdentifier(), 'unit' => $otherUnit->id]))
            ->assertOk()
            ->assertSee('Gudang C18')
            ->assertDontSee('Gudang C17');
    }

    public function test_form_requires_an_explicit_unit_selection(): void
    {
        $warehouse = $this->warehouse();
        $detailUrl = route('customer.gudang.show', ['group' => $warehouse->groupIdentifier(), 'unit_error' => 'required']);

        $this->get(route('customer.temu-janji.create', ['group' => $warehouse->groupIdentifier()]))
            ->assertRedirect($detailUrl);

        $this->get($detailUrl)->assertSee('Pilih unit gudang terlebih dahulu sebelum membuat temu janji.');
    }

    public function test_detail_does_not_automatically_select_a_unit(): void
    {
        $warehouse = $this->warehouse();

        $this->get(route('customer.gudang.show', ['group' => $warehouse->groupIdentifier()]))
            ->assertOk()
            ->assertViewHas('hasSelectedUnit', false)
            ->assertSee(route('customer.temu-janji.create', ['group' => $warehouse->groupIdentifier()]), false);
    }

    public function test_invalid_unit_id_returns_a_validation_message(): void
    {
        $warehouse = $this->warehouse();

        $this->get(route('customer.temu-janji.create', ['group' => $warehouse->groupIdentifier(), 'unit' => 'not-a-uuid']))
            ->assertRedirect(route('customer.gudang.show', ['group' => $warehouse->groupIdentifier(), 'unit_error' => 'invalid']));

        $this->followingRedirects()->get(route('customer.temu-janji.create', ['group' => $warehouse->groupIdentifier(), 'unit' => 'not-a-uuid']))
            ->assertSee('Pilihan unit gudang tidak valid. Silakan pilih kembali.');
    }

    public function test_a_unit_from_another_group_cannot_be_used(): void
    {
        $warehouse = $this->warehouse();
        $otherUnit = $this->warehouse(['id' => '00000000-0000-0000-0000-000000000002', 'blok' => 'D']);

        $this->get(route('customer.temu-janji.create', ['group' => $warehouse->groupIdentifier(), 'unit' => $otherUnit->id]))
            ->assertNotFound();
    }

    public function test_unavailable_unit_returns_to_detail_without_changing_its_status(): void
    {
        $warehouse = $this->warehouse(['status' => 'disewa']);

        $this->get(route('customer.temu-janji.create', ['group' => $warehouse->groupIdentifier(), 'unit' => $warehouse->id]))
            ->assertRedirect(route('customer.gudang.show', ['group' => $warehouse->groupIdentifier(), 'unit' => $warehouse->id, 'unit_error' => 'unavailable']));

        $this->followingRedirects()->get(route('customer.temu-janji.create', ['group' => $warehouse->groupIdentifier(), 'unit' => $warehouse->id]))
            ->assertSee('Unit gudang ini tidak tersedia. Silakan pilih unit lain.');

        $this->assertDatabaseHas('gudang', ['id' => $warehouse->id, 'status' => 'disewa']);
    }

    public function test_invalid_group_and_missing_unit_return_not_found(): void
    {
        $warehouse = $this->warehouse();

        $this->get(route('customer.temu-janji.create', ['group' => 'invalid', 'unit' => $warehouse->id]))->assertNotFound();
        $this->get(route('customer.temu-janji.create', ['group' => $warehouse->groupIdentifier(), 'unit' => '00000000-0000-0000-0000-000000000099']))->assertNotFound();
    }

    public function test_missing_photo_and_price_use_honest_placeholders(): void
    {
        $warehouse = $this->warehouse(['foto' => '[]', 'harga_sewa' => null]);

        $this->get(route('customer.temu-janji.create', ['group' => $warehouse->groupIdentifier(), 'unit' => $warehouse->id]))
            ->assertOk()
            ->assertSee('Foto belum tersedia')
            ->assertSee('Harga belum tersedia')
            ->assertDontSee('warehouse-a5.png');
    }

    public function test_slot_markup_allows_one_selection_and_disables_full_slots(): void
    {
        $warehouse = $this->warehouse();
        $view = $this->view('customer.temu-janji.create', [
            'warehouse' => $warehouse,
            'detailUrl' => route('customer.gudang.show', ['group' => $warehouse->groupIdentifier(), 'unit' => $warehouse->id]),
            'today' => '2026-10-01',
            'storeUrl' => route('customer.temu-janji.store', ['group' => $warehouse->groupIdentifier()]),
            'timeSlots' => [
                ['id' => 'available', 'start' => '08:00', 'end' => '08:30', 'is_full' => false],
                ['id' => 'full', 'start' => '08:30', 'end' => '09:00', 'is_full' => true],
            ],
        ]);
        $document = new \DOMDocument;
        @$document->loadHTML((string) $view);
        $xpath = new \DOMXPath($document);

        $this->assertSame(2, $xpath->query('//input[@type="radio" and @name="jadwal_id"]')->length);
        $this->assertSame(1, $xpath->query('//input[@value="full" and @disabled]')->length);
        $this->assertSame(1, $xpath->query('//input[@value="available" and not(@disabled)]')->length);
    }

    public function test_only_active_database_slots_are_displayed_in_time_order(): void
    {
        $warehouse = $this->warehouse();
        $later = $this->schedule(['jam_mulai' => '14:00:00', 'jam_selesai' => '14:45:00']);
        $earlier = $this->schedule(['id' => '00000000-0000-0000-0000-000000000012']);
        $inactive = $this->schedule(['id' => '00000000-0000-0000-0000-000000000013', 'status_aktif' => false]);

        $this->get(route('customer.temu-janji.create', ['group' => $warehouse->groupIdentifier(), 'unit' => $warehouse->id]))
            ->assertOk()
            ->assertViewHas('timeSlots', fn (array $slots): bool => array_column($slots, 'id') === [$earlier, $later])
            ->assertDontSee($inactive)
            ->assertDontSee('Development Preview');
    }

    public function test_pending_booking_uses_database_values_and_does_not_keep_the_unit(): void
    {
        $this->freezeTime();
        $warehouse = $this->warehouse();
        $payload = $this->payload($warehouse);
        $payload['jam_mulai'] = '23:00:00';
        $payload['jam_selesai'] = '23:59:00';
        $payload['status'] = 'approved';
        $payload['kode_booking'] = 'ATTACKER-CODE';
        $url = route('customer.temu-janji.store', ['group' => $warehouse->groupIdentifier()]);

        $response = $this->postJson($url, $payload)->assertCreated()->assertJsonPath('status', 'pending');
        $code = $response->json('kode_booking');
        $this->assertMatchesRegularExpression('/^BK-[A-F0-9]{24}$/', $code);
        $this->assertDatabaseHas('temu_janji', [
            'kode_booking' => $code,
            'gudang_id' => $warehouse->id,
            'status' => 'pending',
            'jam_mulai' => '09:00:00',
            'jam_selesai' => '09:45:00',
            'nama_perusahaan' => 'PT Customer Test',
            'nama_pic' => 'PIC Test',
            'email' => 'customer@example.com',
            'no_telepon' => '081234567890',
            'tanggal_kunjungan' => $payload['tanggal_kunjungan'],
            'catatan' => 'Periksa kapasitas listrik.',
        ]);
        $this->assertDatabaseHas('gudang', ['id' => $warehouse->id, 'status' => 'tersedia']);

        $second = $this->postJson($url, $payload)->assertCreated();
        $this->assertNotSame($code, $second->json('kode_booking'));
        $this->assertDatabaseCount('temu_janji', 2);
    }

    #[TestWith(['sedang di-keep'])]
    #[TestWith(['tidak tersedia'])]
    #[TestWith(['digunakan'])]
    public function test_submit_rechecks_unit_status_after_the_form_was_opened(string $status): void
    {
        $warehouse = $this->warehouse();
        $payload = $this->payload($warehouse);
        $this->get(route('customer.temu-janji.create', ['group' => $warehouse->groupIdentifier(), 'unit' => $warehouse->id]))->assertOk();
        DB::table('gudang')->where('id', $warehouse->id)->update(['status' => $status]);
        $payload['status'] = 'tersedia';

        $this->postJson(route('customer.temu-janji.store', ['group' => $warehouse->groupIdentifier()]), $payload)
            ->assertUnprocessable()->assertJsonValidationErrors('gudang_id')->assertJsonMissingPath('kode_booking');
        $this->assertDatabaseCount('temu_janji', 0);
    }

    public function test_inactive_and_nonexistent_schedule_ids_are_rejected(): void
    {
        $warehouse = $this->warehouse();
        $payload = $this->payload($warehouse);
        DB::table('jadwal')->update(['status_aktif' => false]);
        $url = route('customer.temu-janji.store', ['group' => $warehouse->groupIdentifier()]);

        $this->postJson($url, $payload)->assertUnprocessable()->assertJsonValidationErrors('jadwal_id');
        $payload['jadwal_id'] = '00000000-0000-0000-0000-000000000099';
        $this->postJson($url, $payload)->assertUnprocessable()->assertJsonValidationErrors('jadwal_id');
        $payload['jadwal_id'] = 'fake';
        $this->postJson($url, $payload)->assertUnprocessable()->assertJsonValidationErrors('jadwal_id');
        $payload['jadwal_id'] = [$this->schedule(['id' => '00000000-0000-0000-0000-000000000015'])];
        $this->postJson($url, $payload)->assertUnprocessable()->assertJsonValidationErrors('jadwal_id');
        $this->assertDatabaseCount('temu_janji', 0);
    }

    public function test_required_fields_are_validated_before_inserting(): void
    {
        $warehouse = $this->warehouse();

        $this->postJson(route('customer.temu-janji.store', ['group' => $warehouse->groupIdentifier()]), [])
            ->assertUnprocessable()->assertJsonValidationErrors([
                'gudang_id', 'jadwal_id', 'nama_perusahaan', 'nama_pic', 'email', 'no_telepon', 'tanggal_kunjungan',
            ])->assertJsonMissingPath('kode_booking');
        $this->assertDatabaseCount('temu_janji', 0);
    }

    public function test_invalid_email_past_date_and_schema_length_limits_are_rejected(): void
    {
        $this->freezeTime();
        $warehouse = $this->warehouse();
        $payload = $this->payload($warehouse);
        $payload['email'] = 'invalid-email';
        $payload['tanggal_kunjungan'] = today()->subDay()->toDateString();
        $payload['nama_perusahaan'] = str_repeat('a', 151);
        $payload['nama_pic'] = str_repeat('a', 101);
        $payload['no_telepon'] = str_repeat('1', 31);

        $this->postJson(route('customer.temu-janji.store', ['group' => $warehouse->groupIdentifier()]), $payload)
            ->assertUnprocessable()->assertJsonValidationErrors(['email', 'tanggal_kunjungan', 'nama_perusahaan', 'nama_pic', 'no_telepon']);
        $this->assertDatabaseCount('temu_janji', 0);
    }

    public function test_unit_id_from_another_group_cannot_be_submitted(): void
    {
        $warehouse = $this->warehouse();
        $payload = $this->payload($warehouse);
        $other = $this->warehouse(['id' => '00000000-0000-0000-0000-000000000002', 'blok' => 'D']);
        $payload['gudang_id'] = $other->id;

        $this->postJson(route('customer.temu-janji.store', ['group' => $warehouse->groupIdentifier()]), $payload)
            ->assertUnprocessable()->assertJsonValidationErrors('gudang_id');
        $this->assertDatabaseCount('temu_janji', 0);
    }

    public function test_insert_failure_returns_error_without_a_success_code(): void
    {
        $warehouse = $this->warehouse();
        $payload = $this->payload($warehouse);
        DB::statement("CREATE TRIGGER reject_booking BEFORE INSERT ON temu_janji BEGIN SELECT RAISE(ABORT, 'test insert failure'); END");

        $this->postJson(route('customer.temu-janji.store', ['group' => $warehouse->groupIdentifier()]), $payload)
            ->assertStatus(503)->assertJsonPath('message', 'Permohonan belum dapat disimpan. Silakan coba kembali beberapa saat lagi.')
            ->assertJsonMissingPath('kode_booking');
        $this->assertDatabaseCount('temu_janji', 0);
        $this->assertDatabaseHas('gudang', ['id' => $warehouse->id, 'status' => 'tersedia']);
    }

    /** @param array<string, string|bool> $attributes */
    private function schedule(array $attributes = []): string
    {
        $data = array_replace([
            'id' => '00000000-0000-0000-0000-000000000011',
            'jam_mulai' => '09:00:00',
            'jam_selesai' => '09:45:00',
            'status_aktif' => true,
        ], $attributes);
        DB::table('jadwal')->insert($data);

        return $data['id'];
    }

    /** @return array<string, string> */
    private function payload(Gudang $warehouse): array
    {
        return [
            'gudang_id' => $warehouse->id,
            'jadwal_id' => $this->schedule(),
            'nama_perusahaan' => 'PT Customer Test',
            'nama_pic' => 'PIC Test',
            'email' => 'customer@example.com',
            'no_telepon' => '081234567890',
            'tanggal_kunjungan' => today()->addDay()->toDateString(),
            'catatan' => 'Periksa kapasitas listrik.',
        ];
    }

    /**
     * @param  array<string, string|null>  $attributes
     */
    private function warehouse(array $attributes = []): Gudang
    {
        $data = array_replace([
            'id' => '00000000-0000-0000-0000-000000000001',
            'unit_code' => 'C17',
            'blok' => 'C',
            'tipe_gudang' => 'Versa',
            'grand_total' => '4400',
            'harga_sewa' => '16000000',
            'foto' => json_encode(['https://example.com/unit-c17.jpg']),
            'status' => 'tersedia',
        ], $attributes);

        DB::table('gudang')->insert($data);

        return Gudang::query()->findOrFail($data['id']);
    }
}
