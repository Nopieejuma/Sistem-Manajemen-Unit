<?php

namespace Tests\Feature;

use Illuminate\Contracts\Encryption\Encrypter;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\TestWith;
use Tests\TestCase;

class CustomerCekBookingTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->assertSame('sqlite', DB::connection()->getDriverName());
        $this->assertSame(':memory:', DB::connection()->getDatabaseName());
        Schema::create('gudang', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('unit_code');
            $table->string('status');
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

    public function test_page_is_public_and_get_never_reveals_a_booking(): void
    {
        $this->createBooking();

        $this->get('/cek-booking?kode_booking=BK-TEST-001&email=customer@example.com')
            ->assertOk()->assertSee('Cek Status Temu Janji')
            ->assertSee('name="_token"', false)
            ->assertSee('method="POST"', false)
            ->assertSee('type="email" required', false)
            ->assertDontSee('BK-TEST-001')->assertDontSee('customer@example.com')
            ->assertDontSee('Ringkasan Temu Janji');
    }

    #[TestWith(['pending', 'Menunggu Persetujuan', 'Permohonan Anda sedang menunggu pemeriksaan admin.'])]
    #[TestWith(['approved', 'Disetujui', 'Permohonan Anda telah disetujui. Silakan mengikuti jadwal kunjungan yang telah ditentukan.'])]
    #[TestWith(['rejected', 'Ditolak', 'Permohonan Anda belum dapat disetujui. Silakan menghubungi admin untuk informasi lebih lanjut.'])]
    #[TestWith(['completed', 'Selesai', 'Kunjungan Anda telah selesai.'])]
    #[TestWith(['canceled', 'Dibatalkan', 'Permohonan temu janji ini telah dibatalkan.'])]
    public function test_matching_code_and_email_show_the_current_status(string $status, string $label, string $message): void
    {
        $this->createBooking(['status' => $status]);
        $beforeBooking = DB::table('temu_janji')->get()->toJson();
        $beforeWarehouse = DB::table('gudang')->get()->toJson();

        $response = $this->post('/cek-booking', $this->credentials());

        $response->assertOk()->assertSee('BK-TEST-001')->assertSee('Gudang C17')
            ->assertSee('2026-11-02')->assertSee('09:00')->assertSee('09:45')
            ->assertSee($label)->assertSee($message)
            ->assertDontSee('customer@example.com')->assertDontSee('PT Private')
            ->assertDontSee('PIC Private')->assertDontSee('081234567890')->assertDontSee('Private notes');
        $this->assertStringContainsString('no-store', $response->headers->get('Cache-Control'));
        $this->assertSame($beforeBooking, DB::table('temu_janji')->get()->toJson());
        $this->assertSame($beforeWarehouse, DB::table('gudang')->get()->toJson());
        $this->get('/cek-booking')->assertDontSee('Ringkasan Temu Janji');
    }

    #[TestWith(['BK-TEST-001', 'wrong@example.com'])]
    #[TestWith(['BK-WRONG', 'customer@example.com'])]
    #[TestWith(["' OR 1=1 --", 'customer@example.com'])]
    public function test_mismatch_returns_the_same_generic_message(string $code, string $email): void
    {
        $this->createBooking();

        $this->post('/cek-booking', ['kode_booking' => $code, 'email' => $email])
            ->assertOk()->assertSee('Data temu janji tidak ditemukan. Periksa kembali kode booking dan email Anda.')
            ->assertDontSee('Ringkasan Temu Janji')->assertDontSee('BK-TEST-001')
            ->assertDontSee('Gudang C17')->assertDontSee('customer@example.com');
    }

    public function test_code_and_email_from_different_customers_do_not_authorize_access(): void
    {
        $this->createBooking();
        $this->createBooking([
            'id' => '00000000-0000-0000-0000-000000000003',
            'kode_booking' => 'BK-OTHER-002',
            'email' => 'other@example.com',
        ]);

        $this->post('/cek-booking', ['kode_booking' => 'BK-OTHER-002', 'email' => 'customer@example.com'])
            ->assertSee('Data temu janji tidak ditemukan. Periksa kembali kode booking dan email Anda.')
            ->assertDontSee('BK-OTHER-002')->assertDontSee('Ringkasan Temu Janji');
        $this->get('/cek-booking/BK-OTHER-002')->assertNotFound();
    }

    #[TestWith([[], 'Kode Booking wajib diisi.'])]
    #[TestWith([['kode_booking' => 'BK-TEST-001'], 'Email Customer wajib diisi.'])]
    #[TestWith([['kode_booking' => 'BK-TEST-001', 'email' => 'invalid'], 'Format email tidak valid.'])]
    #[TestWith([['kode_booking' => ['BK-TEST-001'], 'email' => 'customer@example.com'], 'Kode Booking harus berupa teks.'])]
    #[TestWith([['kode_booking' => '1234567890123456789012345678901', 'email' => 'customer@example.com'], 'Kode Booking maksimal 30 karakter.'])]
    public function test_invalid_input_is_rejected(array $payload, string $message): void
    {
        $this->post('/cek-booking', $payload)->assertUnprocessable()
            ->assertSee($message)->assertDontSee('Ringkasan Temu Janji');
    }

    public function test_post_does_not_accept_credentials_from_query_string(): void
    {
        $this->createBooking();

        $this->post('/cek-booking?kode_booking=BK-TEST-001&email=customer@example.com')
            ->assertUnprocessable()->assertDontSee('Ringkasan Temu Janji');
    }

    public function test_status_updates_are_visible_on_the_next_check(): void
    {
        $this->createBooking();
        $this->post('/cek-booking', $this->credentials())->assertSee('Menunggu Persetujuan');
        DB::table('temu_janji')->update(['status' => 'approved']);

        $this->post('/cek-booking', $this->credentials())->assertSee('Disetujui')->assertDontSee('Menunggu Persetujuan');
    }

    public function test_rate_limit_cannot_be_bypassed_by_changing_credentials(): void
    {
        $this->freezeTime();
        foreach (range(1, 5) as $attempt) {
            $this->post('/cek-booking', [])->assertUnprocessable();
        }

        $this->post('/cek-booking', ['kode_booking' => 'BK-DIFFERENT', 'email' => 'other@example.com'])
            ->assertStatus(429)->assertHeader('Retry-After')
            ->assertSee('Terlalu banyak percobaan.')->assertDontSee('Ringkasan Temu Janji');
        $this->travel(61)->seconds();
        $this->post('/cek-booking', [])->assertUnprocessable();
    }

    public function test_unknown_status_and_unit_code_are_html_escaped(): void
    {
        $this->createBooking(['status' => '<script>alert(1)</script>']);
        DB::table('gudang')->update(['unit_code' => '<script>alert(2)</script>']);

        $this->post('/cek-booking', $this->credentials())
            ->assertOk()->assertSee('&lt;', false)
            ->assertDontSee('<script>', false)
            ->assertSee('Silakan menghubungi admin untuk informasi lebih lanjut.');
    }

    public function test_extra_fields_cannot_change_booking_or_warehouse_status(): void
    {
        $this->createBooking();

        $this->post('/cek-booking', array_merge($this->credentials(), ['status' => 'approved', 'gudang_id' => 'fake']))
            ->assertOk()->assertSee('Menunggu Persetujuan');

        $this->assertDatabaseHas('temu_janji', ['kode_booking' => 'BK-TEST-001', 'status' => 'pending']);
        $this->assertDatabaseHas('gudang', ['unit_code' => 'C17', 'status' => 'tersedia']);
    }

    public function test_post_without_csrf_token_is_rejected(): void
    {
        $this->app->bind(PreventRequestForgery::class, function (Application $app): PreventRequestForgery {
            return new class($app, $app->make(Encrypter::class)) extends PreventRequestForgery
            {
                protected function runningUnitTests(): bool
                {
                    return false;
                }
            };
        });

        $this->post('/cek-booking', $this->credentials())->assertStatus(419);
    }

    public function test_footer_anchors_return_to_home_sections_and_admin_is_not_a_fake_link(): void
    {
        $response = $this->get('/cek-booking');
        $document = new \DOMDocument;
        @$document->loadHTML($response->getContent());
        $xpath = new \DOMXPath($document);
        $links = [];
        foreach ($xpath->query('//footer//a') as $link) {
            $links[trim($link->textContent)] = $link->getAttribute('href');
        }

        $this->assertSame(route('customer.home').'#beranda', $links['Beranda']);
        $this->assertSame(route('customer.home').'#cara-pemesanan', $links['Cara Pemesanan']);
        $this->assertSame(route('customer.gudang.index'), $links['Daftar Gudang']);
        $this->assertSame(0, $xpath->query('//header//a[normalize-space(.)="Admin"]')->length);
        $this->assertSame(1, $xpath->query('//header//span[@aria-disabled="true" and normalize-space(.)="Admin"]')->length);
    }

    /** @return array<string, string> */
    private function credentials(): array
    {
        return ['kode_booking' => 'BK-TEST-001', 'email' => 'customer@example.com'];
    }

    /** @param array<string, string> $attributes */
    private function createBooking(array $attributes = []): void
    {
        DB::table('gudang')->insertOrIgnore([
            'id' => '00000000-0000-0000-0000-000000000001',
            'unit_code' => 'C17',
            'status' => 'tersedia',
        ]);
        DB::table('temu_janji')->insert(array_replace([
            'id' => '00000000-0000-0000-0000-000000000002',
            'kode_booking' => 'BK-TEST-001',
            'gudang_id' => '00000000-0000-0000-0000-000000000001',
            'nama_perusahaan' => 'PT Private',
            'nama_pic' => 'PIC Private',
            'email' => 'customer@example.com',
            'no_telepon' => '081234567890',
            'tanggal_kunjungan' => '2026-11-02',
            'jam_mulai' => '09:00:00',
            'jam_selesai' => '09:45:00',
            'catatan' => 'Private notes',
            'status' => 'pending',
            'created_at' => '2026-10-26 09:00:00',
            'updated_at' => '2026-10-26 09:00:00',
        ], $attributes));
    }
}
