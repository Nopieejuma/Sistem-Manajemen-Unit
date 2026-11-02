<?php

namespace Tests\Feature;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class DeploymentConfigurationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['app.url' => 'https://deployment.example.test']);
    }

    protected function tearDown(): void
    {
        Request::setTrustedHosts([]);
        parent::tearDown();
    }

    public function test_container_uses_forwarded_https_without_trusting_forwarded_host(): void
    {
        config(['deployment.container' => true]);

        $this->get('http://deployment.example.test/admin/dashboard', [
            'X-Forwarded-Proto' => 'https',
            'X-Forwarded-Port' => '443',
            'X-Forwarded-Host' => 'attacker.example.test',
        ])->assertRedirect('https://deployment.example.test/admin/login');
    }

    public function test_container_refuses_an_unconfigured_host(): void
    {
        config(['deployment.container' => true]);

        $this->get('http://attacker.example.test/admin/login', [
            'X-Forwarded-Proto' => 'https',
        ])->assertStatus(400);
    }

    public function test_local_mode_does_not_trust_proxy_headers_or_restrict_local_hosts(): void
    {
        config(['deployment.container' => false]);

        $this->get('http://localhost/admin/dashboard', [
            'X-Forwarded-Proto' => 'https',
        ])->assertRedirect('http://localhost/admin/login');
    }

    public function test_guest_pdf_signature_survives_https_termination_at_the_proxy(): void
    {
        config(['deployment.container' => true]);
        $this->createAdminSqliteSchema();
        DB::table('gudang')->insert([
            'id' => '00000000-0000-4000-8000-000000000001',
            'unit_code' => 'CONTAINER-TEST', 'tipe_gudang' => 'Versa',
        ]);
        DB::table('temu_janji')->insert([
            'id' => '00000000-0000-4000-8000-000000000002',
            'kode_booking' => 'BK-CONTAINER-TEST',
            'gudang_id' => '00000000-0000-4000-8000-000000000001',
            'nama_perusahaan' => 'Synthetic Test', 'nama_pic' => 'Test',
            'email' => 'test@example.test', 'no_telepon' => '0800000000',
            'tanggal_kunjungan' => '2099-12-25',
            'jam_mulai' => '09:00:00', 'jam_selesai' => '10:00:00', 'status' => 'pending',
        ]);
        Route::get('/_test/receipt-link', fn (): string => URL::temporarySignedRoute(
            'customer.booking.receipt', now()->addHour(), ['kodeBooking' => 'BK-CONTAINER-TEST'],
        ));
        $headers = ['X-Forwarded-Proto' => 'https', 'X-Forwarded-Port' => '443'];
        $url = $this->get('http://deployment.example.test/_test/receipt-link', $headers)
            ->assertOk()->getContent();
        $this->assertStringStartsWith('https://deployment.example.test/bukti-booking/', $url);
        $backendUrl = preg_replace('~^https://~', 'http://', $url);

        $this->get($backendUrl, $headers)->assertOk()
            ->assertHeader('Content-Type', 'application/pdf')
            ->assertDownload('bukti-BK-CONTAINER-TEST.pdf');
        $this->get($backendUrl, ['X-Forwarded-Proto' => 'http'])->assertForbidden();
        $this->assertDatabaseHas('temu_janji', ['kode_booking' => 'BK-CONTAINER-TEST', 'status' => 'pending']);
    }
}
