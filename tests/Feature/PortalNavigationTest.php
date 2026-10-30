<?php

namespace Tests\Feature;

use App\Models\Gudang;
use App\Models\Jadwal;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class PortalNavigationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->createAdminSqliteSchema();
    }

    public function test_guest_customer_portal_uses_existing_admin_login_and_keeps_dashboard_protected(): void
    {
        $response = $this->get(route('customer.home'))->assertOk();
        $this->assertSame(route('admin.login'), $this->linkDestination($response->getContent(), 'Admin'));

        $this->get(route('admin.login'))->assertOk()->assertViewIs('auth.login');
        $this->get(route('admin.dashboard'))->assertRedirect(route('admin.login', absolute: false));
        $this->assertGuest();
    }

    public function test_admin_can_visit_customer_and_return_without_logging_in_again_until_logout(): void
    {
        $user = User::factory()->create();

        $this->post(route('admin.login.submit'), ['username' => $user->username, 'password' => 'password'])
            ->assertRedirect(route('admin.dashboard', absolute: false));
        $this->app['auth']->forgetGuards();
        $dashboard = $this->get(route('admin.dashboard'))->assertOk();
        $customerUrl = $this->linkDestination($dashboard->getContent(), 'Lihat Website Customer');
        $this->assertSame(route('customer.home'), $customerUrl);

        $this->app['auth']->forgetGuards();
        $customer = $this->get($customerUrl)->assertOk()->assertViewIs('customer.home');
        $this->assertAuthenticatedAs($user);
        $adminUrl = $this->linkDestination($customer->getContent(), 'Admin');
        $this->app['auth']->forgetGuards();
        $this->get($adminUrl)->assertRedirect(route('admin.dashboard', absolute: false));
        $this->get(route('admin.dashboard'))->assertOk();
        $this->assertAuthenticatedAs($user);

        $this->post(route('admin.logout'))->assertRedirect(route('admin.login', absolute: false));
        $this->app['auth']->forgetGuards();
        $this->get($adminUrl)->assertOk()->assertViewIs('auth.login');
        $this->get(route('admin.dashboard'))->assertRedirect(route('admin.login', absolute: false));
        $this->get(route('customer.home'))->assertOk();
        $this->assertGuest();
    }

    public function test_all_authenticated_admin_pages_offer_the_customer_portal(): void
    {
        $user = User::factory()->create();
        $warehouse = Gudang::create(['unit_code' => 'PORTAL-01', 'tipe_gudang' => 'Versa', 'status' => 'tersedia']);
        $schedule = Jadwal::create([
            'id' => (string) Str::uuid(), 'jam_mulai' => '09:00:00', 'jam_selesai' => '09:45:00',
            'kuota' => 1, 'status_aktif' => true,
        ]);
        $bookingId = (string) Str::uuid();
        DB::table('temu_janji')->insert([
            'id' => $bookingId, 'kode_booking' => 'BK-PORTAL-01', 'gudang_id' => $warehouse->id,
            'nama_perusahaan' => 'PT Portal Test', 'nama_pic' => 'PIC Portal', 'email' => 'portal@example.test',
            'no_telepon' => '08123456789', 'tanggal_kunjungan' => '2026-11-10',
            'jam_mulai' => '09:00:00', 'jam_selesai' => '09:45:00', 'status' => 'pending',
        ]);
        $this->actingAs($user);

        foreach ([
            route('admin.dashboard'), route('admin.gudang.index'), route('admin.gudang.create'),
            route('admin.gudang.edit', $warehouse), route('admin.jadwal.index'), route('admin.jadwal.create'),
            route('admin.jadwal.edit', $schedule), route('admin.temu-janji.index'), route('admin.temu-janji.show', $bookingId),
        ] as $url) {
            $response = $this->get($url)->assertOk();
            $this->assertSame(route('customer.home'), $this->linkDestination($response->getContent(), 'Lihat Website Customer'));
        }
    }

    private function linkDestination(string $html, string $label): string
    {
        $document = new \DOMDocument;
        @$document->loadHTML($html);
        $xpath = new \DOMXPath($document);
        $links = $xpath->query('//a[normalize-space(.)="'.$label.'"]');
        $this->assertSame(1, $links->length);

        return $links->item(0)->getAttribute('href');
    }
}
