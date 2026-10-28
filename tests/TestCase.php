<?php

namespace Tests;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

abstract class TestCase extends BaseTestCase
{
    protected function createAdminSqliteSchema(): void
    {
        if (app()->environment() !== 'testing' || DB::connection()->getDriverName() !== 'sqlite' || DB::connection()->getDatabaseName() !== ':memory:') {
            throw new \RuntimeException('Admin feature tests require the SQLite in-memory database.');
        }

        if (! Schema::hasTable('user')) {
            Schema::create('user', function (Blueprint $table): void {
                $table->bigInteger('id')->primary();
                $table->string('username');
                $table->string('password')->nullable();
                $table->timestamp('created_at')->useCurrent();
            });
        }

        if (! Schema::hasTable('gudang')) {
            Schema::create('gudang', function (Blueprint $table): void {
                $table->uuid('id')->primary();
                $table->string('unit_code', 20)->unique();
                $table->string('blok', 10)->nullable();
                $table->string('tipe_gudang');
                $table->decimal('tinggi', 10, 2)->nullable();
                $table->decimal('kavling_l', 10, 2)->nullable();
                $table->decimal('kavling_p', 10, 2)->nullable();
                $table->decimal('luas_kavling', 10, 2)->nullable();
                $table->decimal('lt1_l', 10, 2)->nullable();
                $table->decimal('lt1_p', 10, 2)->nullable();
                $table->decimal('luas_lt1', 10, 2)->nullable();
                $table->decimal('lt2_l', 10, 2)->nullable();
                $table->decimal('lt2_p', 10, 2)->nullable();
                $table->decimal('extra_m2', 10, 2)->default(0);
                $table->decimal('luas_lt2', 10, 2)->nullable();
                $table->decimal('pos_l', 10, 2)->nullable();
                $table->decimal('pos_p', 10, 2)->nullable();
                $table->decimal('luas_pos', 10, 2)->nullable();
                $table->decimal('total_luas_bangunan', 10, 2)->nullable();
                $table->decimal('grand_total', 10, 2)->nullable();
                $table->json('foto')->nullable();
                $table->decimal('harga_sewa', 14, 2)->nullable()->default(0);
                $table->string('status', 50)->nullable()->default('tersedia');
                $table->json('fasilitas')->nullable();
                $table->timestamp('created_at')->nullable();
            });
        }

        if (! Schema::hasTable('jadwal')) {
            Schema::create('jadwal', function (Blueprint $table): void {
                $table->uuid('id')->primary();
                $table->time('jam_mulai');
                $table->time('jam_selesai');
                $table->integer('kuota')->default(1);
                $table->boolean('status_aktif')->default(true);
                $table->timestampsTz();
                $table->unique(['jam_mulai', 'jam_selesai']);
            });
        }

        if (! Schema::hasTable('temu_janji')) {
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
    }
}
