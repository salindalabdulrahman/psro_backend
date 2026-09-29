<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Adds videos JSON column for multiple video URLs per post (e.g. Facebook Watch links).
     * Shape: [{"url": "https://...", "title": "Optional label"}]
     */
    public function up(): void
    {
        Schema::table('posts', function (Blueprint $table) {
            $table->json('videos')->nullable()->after('cta_link');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('posts', function (Blueprint $table) {
            $table->dropColumn('videos');
        });
    }
};
