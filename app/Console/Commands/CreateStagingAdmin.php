<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;

#[Signature('staging:create-admin {username} {--expect-host=} {--expect-database=} {--expect-user=}')]
#[Description('Create the first admin only on an explicitly verified, empty staging database')]
class CreateStagingAdmin extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        if (! app()->environment('staging') || DB::getDriverName() !== 'pgsql' || ! $this->input->isInteractive()) {
            $this->error('Hanya tersedia secara interaktif pada APP_ENV=staging dengan PostgreSQL.');

            return self::FAILURE;
        }
        $connection = DB::connection();
        foreach (['host' => 'expect-host', 'database' => 'expect-database', 'username' => 'expect-user'] as $setting => $option) {
            $expected = $this->option($option);
            if (! is_string($expected) || $expected === '' || $expected !== (string) $connection->getConfig($setting)) {
                $this->error('Identitas database tidak cocok dengan opsi --expect-*; tidak ada akun yang dibuat.');

                return self::FAILURE;
            }
        }
        foreach (['user', 'gudang', 'jadwal', 'temu_janji'] as $table) {
            if (! Schema::hasTable($table) || DB::table($table)->exists()) {
                $this->error('Provisioning ditolak: schema belum lengkap atau database sudah berisi akun/data bisnis.');

                return self::FAILURE;
            }
        }
        $password = $this->secret('Password Admin staging', false);
        $confirmation = $this->secret('Ulangi password Admin staging', false);
        $data = ['username' => $this->argument('username'), 'password' => $password, 'password_confirmation' => $confirmation];
        $validator = Validator::make($data, [
            'username' => ['required', 'string', 'regex:/\A[a-zA-Z0-9_.-]{3,50}\z/'],
            'password' => ['required', 'confirmed', Password::min(12)->mixedCase()->numbers()],
        ]);
        if ($validator->fails()) {
            $this->error('Username harus 3–50 karakter; password minimal 12 karakter dengan huruf besar, kecil, angka, dan konfirmasi yang sama.');

            return self::FAILURE;
        }

        return DB::transaction(function () use ($data): int {
            DB::select('SELECT pg_advisory_xact_lock(1397313601, 1)');
            foreach (['user', 'gudang', 'jadwal', 'temu_janji'] as $table) {
                if (DB::table($table)->exists()) {
                    $this->error('Database tidak lagi kosong; provisioning dibatalkan.');

                    return self::FAILURE;
                }
            }
            User::query()->create(['username' => $data['username'], 'password' => $data['password']]);
            $this->info('Admin staging dibuat. Password tidak ditampilkan dan akun lama tidak diubah.');

            return self::SUCCESS;
        });
    }
}
