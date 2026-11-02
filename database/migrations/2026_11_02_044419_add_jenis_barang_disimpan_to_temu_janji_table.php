<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (! Schema::hasTable('temu_janji')) {
            return;
        }

        Schema::table('temu_janji', function (Blueprint $table): void {
            $table->text('jenis_barang_disimpan')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (! Schema::hasTable('temu_janji')) {
            return;
        }

        Schema::table('temu_janji', function (Blueprint $table): void {
            $table->dropColumn('jenis_barang_disimpan');
        });
    }
};
