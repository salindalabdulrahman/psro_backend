<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Update Facebook social link URL to PSRO OCM page.
     */
    public function up(): void
    {
        $rows = DB::table('header_settings')->get();
        foreach ($rows as $row) {
            $links = json_decode($row->social_links_json, true);
            if (! is_array($links)) {
                continue;
            }
            $updated = false;
            foreach ($links as &$link) {
                if (isset($link['platform']) && $link['platform'] === 'facebook') {
                    $link['url'] = 'https://www.facebook.com/psro.ocm';
                    $updated = true;
                    break;
                }
            }
            if ($updated) {
                DB::table('header_settings')
                    ->where('id', $row->id)
                    ->update([
                        'social_links_json' => json_encode($links),
                        'updated_at' => now(),
                    ]);
            }
        }
    }

    public function down(): void
    {
        $rows = DB::table('header_settings')->get();
        foreach ($rows as $row) {
            $links = json_decode($row->social_links_json, true);
            if (! is_array($links)) {
                continue;
            }
            $updated = false;
            foreach ($links as &$link) {
                if (isset($link['platform']) && $link['platform'] === 'facebook') {
                    $link['url'] = 'https://facebook.com';
                    $updated = true;
                    break;
                }
            }
            if ($updated) {
                DB::table('header_settings')
                    ->where('id', $row->id)
                    ->update([
                        'social_links_json' => json_encode($links),
                        'updated_at' => now(),
                    ]);
            }
        }
    }
};
