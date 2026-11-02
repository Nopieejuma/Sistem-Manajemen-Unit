<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (! Schema::hasTable('user')) {
            Schema::create('user', function (Blueprint $table): void {
                $table->id();
                $table->string('username')->unique();
                $table->string('password')->nullable();
                $table->timestampTz('created_at')->nullable()->useCurrent();
            });
        }

        if (! Schema::hasTable('gudang')) {
            Schema::create('gudang', function (Blueprint $table): void {
                $table->uuid('id')->primary();
                $table->string('unit_code', 20)->unique();
                $table->string('blok', 10)->nullable();
                $table->enum('tipe_gudang', ['Versa', 'Heksa', 'MAXI 24', 'VERTA 56']);
                foreach (['tinggi', 'kavling_l', 'kavling_p', 'luas_kavling', 'lt1_l', 'lt1_p', 'luas_lt1',
                    'lt2_l', 'lt2_p', 'luas_lt2', 'pos_l', 'pos_p', 'luas_pos', 'total_luas_bangunan', 'grand_total'] as $column) {
                    $table->decimal($column, 10, 2)->nullable();
                }
                $table->decimal('extra_m2', 10, 2)->default(0);
                $table->jsonb('foto')->nullable();
                $table->jsonb('fasilitas')->nullable();
                $table->decimal('harga_sewa', 14, 2)->nullable()->default(0);
                $table->enum('status', ['tersedia', 'sedang di-keep', 'tidak tersedia', 'digunakan'])->nullable()->default('tersedia');
                $table->timestampTz('created_at')->nullable()->useCurrent();
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
                $table->unique(['jam_mulai', 'jam_selesai'], 'jadwal_jam_unique');
            });
            if (DB::getDriverName() === 'pgsql') {
                DB::statement('ALTER TABLE jadwal ADD CONSTRAINT jadwal_kuota_positive CHECK (kuota >= 1)');
                DB::statement('ALTER TABLE jadwal ADD CONSTRAINT jadwal_jam_berurutan CHECK (jam_selesai > jam_mulai)');
            }
        }

        if (! Schema::hasTable('temu_janji')) {
            Schema::create('temu_janji', function (Blueprint $table): void {
                $table->uuid('id')->primary();
                $table->string('kode_booking', 30)->unique();
                $table->foreignUuid('gudang_id')->constrained('gudang')->restrictOnDelete();
                $table->string('nama_perusahaan', 150);
                $table->string('nama_pic', 100);
                $table->string('email', 150);
                $table->string('no_telepon', 30);
                $table->date('tanggal_kunjungan');
                $table->time('jam_mulai');
                $table->time('jam_selesai');
                $table->text('catatan')->nullable();
                $table->text('jenis_barang_disimpan')->nullable();
                $table->enum('status', ['pending', 'approved', 'rejected', 'completed', 'canceled'])->default('pending');
                $table->timestampsTz();
                $table->index(['tanggal_kunjungan', 'jam_mulai', 'jam_selesai', 'status'], 'temu_janji_kapasitas_idx');
                $table->index(['gudang_id', 'status'], 'temu_janji_gudang_status_idx');
            });
            if (DB::getDriverName() === 'pgsql') {
                DB::statement('ALTER TABLE temu_janji ADD CONSTRAINT temu_janji_jam_berurutan CHECK (jam_selesai > jam_mulai)');
            }
        } elseif (! Schema::hasColumn('temu_janji', 'jenis_barang_disimpan')) {
            Schema::table('temu_janji', function (Blueprint $table): void {
                $table->text('jenis_barang_disimpan')->nullable();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        throw new RuntimeException('Migration baseline bersifat forward-only; rollback ditolak untuk melindungi tabel dan data lama.');
    }
};
