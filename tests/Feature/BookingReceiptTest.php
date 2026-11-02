<?php

namespace Tests\Feature;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class BookingReceiptTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->createAdminSqliteSchema();
        DB::table('gudang')->insert([
            'id' => '00000000-0000-4000-8000-000000000001',
            'unit_code' => 'C17', 'blok' => 'C', 'tipe_gudang' => 'Versa',
        ]);
        DB::table('temu_janji')->insert([
            'id' => '00000000-0000-4000-8000-000000000002',
            'kode_booking' => 'BK-123456789012345678901234',
            'gudang_id' => '00000000-0000-4000-8000-000000000001',
            'nama_perusahaan' => 'Private Company', 'nama_pic' => 'Private Person',
            'email' => 'private@example.com', 'no_telepon' => '081234567890',
            'tanggal_kunjungan' => '2099-12-25', 'jam_mulai' => '09:00:00',
            'jam_selesai' => '09:45:00', 'status' => 'pending',
        ]);
    }

    public function test_guest_downloads_saved_booking_without_customer_personal_data(): void
    {
        $url = $this->receiptUrl();
        $this->assertStringNotContainsString('private', $url);
        $response = $this->get($url)->assertOk()->assertHeader('Content-Type', 'application/pdf')
            ->assertDownload('bukti-BK-123456789012345678901234.pdf');
        $pdf = $response->getContent();
        $this->assertStringStartsWith('%PDF-1.4', $pdf);
        foreach (['BK-123456789012345678901234', 'C17', 'Versa', '25-12-2099', '09:00 - 09:45 WIB', 'Status Pengajuan: pending', 'bukan persetujuan Admin'] as $text) {
            $this->assertStringContainsString($text, $pdf);
        }
        foreach (['Private Company', 'Private Person', 'private@example.com', '081234567890'] as $text) {
            $this->assertStringNotContainsString($text, $pdf);
        }
        DB::table('temu_janji')->update(['status' => 'rejected']);
        $this->assertStringContainsString('Status Pengajuan: rejected', $this->get($url)->assertOk()->getContent());
    }

    public function test_unsigned_expired_and_tampered_downloads_are_refused(): void
    {
        $this->get(route('customer.booking.receipt', ['kodeBooking' => 'BK-123456789012345678901234']))->assertForbidden();
        $this->get(str_replace('BK-123456789012345678901234', 'BK-000000000000000000000000', $this->receiptUrl()))->assertForbidden();
        $expired = URL::temporarySignedRoute('customer.booking.receipt', now()->subMinute(), ['kodeBooking' => 'BK-123456789012345678901234']);
        $this->get($expired)->assertForbidden();
    }

    public function test_valid_signature_for_missing_booking_returns_not_found(): void
    {
        DB::table('temu_janji')->delete();
        $this->get($this->receiptUrl())->assertNotFound();
    }

    public function test_migration_preserves_old_bookings_and_allows_null_goods(): void
    {
        Schema::table('temu_janji', function (Blueprint $table): void {
            $table->dropColumn('jenis_barang_disimpan');
        });
        $migration = require database_path('migrations/2026_11_02_044419_add_jenis_barang_disimpan_to_temu_janji_table.php');
        $migration->up();
        $this->assertDatabaseHas('temu_janji', ['kode_booking' => 'BK-123456789012345678901234', 'jenis_barang_disimpan' => null]);
    }

    private function receiptUrl(): string
    {
        return URL::temporarySignedRoute('customer.booking.receipt', now()->addDay(), ['kodeBooking' => 'BK-123456789012345678901234']);
    }
}
