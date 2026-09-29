<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('posts', function (Blueprint $table) {
            $table->string('highlight_name', 255)->nullable()->after('description');
            $table->string('highlight_role', 255)->nullable()->after('highlight_name');
            $table->text('highlight_quote')->nullable()->after('highlight_role');
        });
    }

    public function down(): void
    {
        Schema::table('posts', function (Blueprint $table) {
            $table->dropColumn(['highlight_name', 'highlight_role', 'highlight_quote']);
        });
    }
};
