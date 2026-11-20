<?php

namespace App\Console\Commands;

use App\Enums\RoleName;
use App\Models\User;
use App\Services\ActivityLogger;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;

/**
 * Membuat atau mempromosikan akun Owner. Password tidak pernah di-hardcode dan
 * tidak diterima lewat argumen command (agar tidak terekspos di shell history
 * atau daftar proses); selalu diminta secara interaktif dan dikonfirmasi.
 */
class ProvisionOwnerCommand extends Command
{
    protected $signature = 'gearora:provision-owner
        {--name= : Nama pemilik}
        {--email= : Email pemilik}';

    protected $description = 'Membuat akun Owner GEARORA dengan role owner.';

    public function handle(ActivityLogger $logger): int
    {
        if (! DB::table('roles')->where('name', RoleName::Owner->value)->exists()) {
            $this->error('Role belum tersedia. Jalankan php artisan db:seed --class=RolePermissionSeeder terlebih dahulu.');

            return self::FAILURE;
        }

        $name = (string) ($this->option('name') ?: $this->ask('Nama pemilik'));
        $email = (string) ($this->option('email') ?: $this->ask('Email pemilik'));

        // Password hanya lewat prompt rahasia; tidak ada opsi CLI dan tidak ada default.
        $password = (string) $this->secret('Password Owner (min 8 karakter, kombinasi huruf & angka)');
        $confirmation = (string) $this->secret('Ulangi password Owner');

        if ($password !== $confirmation) {
            $this->error('Konfirmasi password tidak cocok.');

            return self::FAILURE;
        }

        $validator = Validator::make(
            ['name' => $name, 'email' => $email, 'password' => $password],
            [
                'name' => ['required', 'string', 'min:3', 'max:120'],
                'email' => ['required', 'string', 'email:rfc', 'max:190'],
                'password' => ['required', 'string', 'min:8', Password::min(8)->letters()->numbers()],
            ],
        );

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->error($error);
            }

            return self::FAILURE;
        }

        $user = DB::transaction(function () use ($name, $email, $password, $logger) {
            $user = User::query()->updateOrCreate(
                ['email' => $email],
                ['name' => $name, 'password' => $password, 'is_active' => true],
            );

            $user->assignRole(RoleName::Owner);

            $logger->log('owner.provisioned', $user, "Akun Owner {$user->email} disiapkan.");

            return $user;
        });

        $this->info("Owner siap: {$user->email} (id {$user->getKey()}).");
        $this->warn('Simpan password di tempat aman dan jangan bagikan akun Owner.');

        return self::SUCCESS;
    }
}
