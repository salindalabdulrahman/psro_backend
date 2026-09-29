<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('username')->nullable()->after('name');
            $table->string('first_name')->nullable()->after('username');
            $table->string('last_name')->nullable()->after('first_name');
            $table->string('nickname')->nullable()->after('last_name');
            $table->string('website')->nullable()->after('email');
            $table->text('bio')->nullable()->after('website');
        });

        // Populate existing users from name and email
        $users = DB::table('users')->get();
        foreach ($users as $user) {
            $parts = explode(' ', $user->name ?? '', 2);
            $username = $user->email ? explode('@', $user->email)[0] : null;
            DB::table('users')->where('id', $user->id)->update([
                'username' => $username,
                'first_name' => $parts[0] ?? null,
                'last_name' => $parts[1] ?? null,
                'nickname' => $user->name,
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['username', 'first_name', 'last_name', 'nickname', 'website', 'bio']);
        });
    }
};
