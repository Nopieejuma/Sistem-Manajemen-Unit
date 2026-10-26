<?php

namespace Tests\Feature;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    /**
     * A basic test example.
     */
    public function test_the_application_returns_a_successful_response(): void
    {
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

        $response = $this->get('/');

        $response->assertStatus(200);
    }
}
