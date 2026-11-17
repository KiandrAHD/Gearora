<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

/**
 * Nilai pengaturan default non-rahasia. Instruksi bank kosong secara default
 * dan harus diisi operator; jangan menaruh nomor rekening palsu sebagai data nyata.
 */
class SettingsSeeder extends Seeder
{
    public function run(): void
    {
        $settings = [
            ['key' => 'site_name', 'value' => 'GEARORA', 'group' => 'general'],
            ['key' => 'site_tagline', 'value' => 'Gear Up. Explore More.', 'group' => 'general'],
            ['key' => 'bank_name', 'value' => Setting::get('bank_name'), 'group' => 'payment'],
            ['key' => 'bank_account_number', 'value' => Setting::get('bank_account_number'), 'group' => 'payment'],
            ['key' => 'bank_account_name', 'value' => Setting::get('bank_account_name'), 'group' => 'payment'],
            ['key' => 'cash_location', 'value' => Setting::get('cash_location'), 'group' => 'payment'],
        ];

        foreach ($settings as $setting) {
            Setting::query()->updateOrCreate(
                ['key' => $setting['key']],
                ['value' => $setting['value'], 'group' => $setting['group']],
            );
        }
    }
}
