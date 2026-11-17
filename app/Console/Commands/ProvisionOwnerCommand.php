<?php

namespace App\Console\Commands;

use App\Enums\RoleName;
use App\Models\User;
use App\Services\ActivityLogger;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

/**
 * Membuat atau mempromosikan akun Owner. Password tidak pernah di-hardcode
 * dan selalu diminta secara interaktif (atau lewat opsi rahasia saat otomasi).
 */
class ProvisionOwnerCommand extends Command
{
    protected $signature = 'gearora:provision-owner
        {--name= : Nama pemilik}
        {--email= : Email pemilik}
        {--password= : Password (kosongkan untuk diminta interaktif)}';

    protected $description = 'Membuat akun Owner GEARORA dengan role owner.';

    public function handle(ActivityLogger $logger): int
    {
        if (! DB::table('roles')->where('name', RoleName::Owner->value)->exists()) {
            $this->error('Role belum tersedia. Jalankan php artisan db:seed --class=RolePermissionSeeder terlebih dahulu.');

            return self::FAILURE;
        }

        $name = (string) ($this->option('name') ?: $this->ask('Nama pemilik'));
        $email = (string) ($this->option('email') ?: $this->ask('Email pemilik'));
        $password = (string) ($this->option('password') ?: $this->secret('Password (min 8 karakter, kombinasi huruf & angka)'));

        $validator = Validator::make(
            ['name' => $name, 'email' => $email, 'password' => $password],
            [
                'name' => ['required', 'string', 'min:3', 'max:120'],
                'email' => ['required', 'string', 'email:rfc', 'max:190'],
                'password' => ['required', 'string', 'min:8'],
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
        $this->warn('Segera ganti password default dan pastikan hanya Owner yang memegang akun ini.');

        return self::SUCCESS;
    }
}
