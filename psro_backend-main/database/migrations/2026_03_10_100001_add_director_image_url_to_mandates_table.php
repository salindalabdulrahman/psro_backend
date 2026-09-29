<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('mandates', function (Blueprint $table) {
            $table->string('director_image_url', 512)->nullable()->after('director_title');
        });
    }

    public function down(): void
    {
        Schema::table('mandates', function (Blueprint $table) {
            $table->dropColumn('director_image_url');
        });
    }
};
