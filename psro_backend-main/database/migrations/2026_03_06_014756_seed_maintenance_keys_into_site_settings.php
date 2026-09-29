<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $keys = ['maintenance_mode', 'maintenance_message', 'maintenance_estimated_back'];
        $existing = DB::table('site_settings')->whereIn('key', $keys)->pluck('key')->toArray();

        $rows = [];

        if (!in_array('maintenance_mode', $existing)) {
            $rows[] = [
                'key' => 'maintenance_mode',
                'value' => '0',
                'type' => 'boolean',
                'group' => 'maintenance',
                'description' => 'Toggle site maintenance mode on or off',
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        if (!in_array('maintenance_message', $existing)) {
            $rows[] = [
                'key' => 'maintenance_message',
                'value' => 'We\'re currently performing scheduled maintenance. Please check back shortly.',
                'type' => 'text',
                'group' => 'maintenance',
                'description' => 'Message shown to visitors during maintenance',
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        if (!in_array('maintenance_estimated_back', $existing)) {
            $rows[] = [
                'key' => 'maintenance_estimated_back',
                'value' => '',
                'type' => 'text',
                'group' => 'maintenance',
                'description' => 'Estimated time the site will be back online',
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        if (count($rows) > 0) {
            DB::table('site_settings')->insert($rows);
        }
    }

    public function down(): void
    {
        DB::table('site_settings')->whereIn('key', [
            'maintenance_mode',
            'maintenance_message',
            'maintenance_estimated_back',
        ])->delete();
    }
};
