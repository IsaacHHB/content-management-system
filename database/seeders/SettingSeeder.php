<?php

namespace Database\Seeders;

use App\Models\Menu;
use App\Models\Setting;
use Illuminate\Database\Seeder;

class SettingSeeder extends Seeder
{
    public function run(): void
    {
        $settings = [
            'contact' => ['contact_email' => '', 'contact_phone' => '', 'mailing_address' => ''],
            'social' => ['facebook_url' => '', 'instagram_url' => '', 'youtube_url' => ''],
            'seo' => ['google_analytics_id' => ''],
            'general' => ['site_name' => 'Native Dads Network', 'tagline' => '', 'logo' => null, 'footer_text' => '', 'partner_banner' => null],
        ];

        foreach ($settings as $group => $values) {
            foreach ($values as $key => $value) {
                Setting::updateOrCreate(['key' => $key], ['value' => $value, 'group' => $group]);
            }
        }

        Menu::updateOrCreate(['slot' => 'header'], ['name' => 'Header']);
        Menu::updateOrCreate(['slot' => 'footer'], ['name' => 'Footer']);
    }
}
