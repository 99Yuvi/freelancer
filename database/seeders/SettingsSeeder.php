<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SettingsSeeder extends Seeder
{
    public function run(): void
    {
        $settings = [
            ['key_name' => 'commission_rate',         'value' => '12.00', 'type' => 'decimal', 'group_name' => 'payments',  'label' => 'Platform Commission %'],
            ['key_name' => 'max_active_proposals',    'value' => '5',     'type' => 'integer', 'group_name' => 'proposals', 'label' => 'Max simultaneous proposals per freelancer'],
            ['key_name' => 'review_window_days',      'value' => '14',    'type' => 'integer', 'group_name' => 'reviews',   'label' => 'Days after contract to submit review'],
            ['key_name' => 'min_reviews_for_rating',  'value' => '3',     'type' => 'integer', 'group_name' => 'reviews',   'label' => 'Min reviews before public rating shown'],
            ['key_name' => 'max_file_upload_mb',      'value' => '10',    'type' => 'integer', 'group_name' => 'uploads',   'label' => 'Max upload size in MB'],
        ];

        foreach ($settings as $setting) {
            DB::table('settings')->updateOrInsert(
                ['key_name' => $setting['key_name']],
                $setting
            );
        }
    }
}
