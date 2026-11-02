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
        Schema::create('warehouse_photo_uploads', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->bigInteger('admin_id')->index();
            $table->uuid('warehouse_id')->nullable()->index();
            $table->string('staging_key')->unique();
            $table->string('public_key')->unique();
            $table->text('public_url');
            $table->string('mime', 32);
            $table->unsignedInteger('size');
            $table->string('status', 24)->default('pending');
            $table->timestamp('expires_at');
            $table->index(['status', 'expires_at']);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('warehouse_photo_uploads');
    }
};
