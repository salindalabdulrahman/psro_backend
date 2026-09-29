<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('footer_links', function (Blueprint $table) {
            $table->id();
            $table->enum('group', ['ministry', 'agency']);
            $table->string('name', 255);
            $table->string('url', 512)->default('#');
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index('group');
            $table->index('sort_order');
        });

        $now = now();
        $ministries = [
            'Agriculture, Fisheries, and Agrarian Reform',
            'Basic, Higher and Technical Education',
            'Environment, Natural Resources and Energy',
            'Finance, Budget and Management',
            'Health',
            'Human Settlements & Development',
            "Indigenous Peoples' Affairs",
            'Labor and Employment',
            'Interior and Local Government',
            'Public Order and Safety',
            'Public Works',
            'Science and Technology',
            'Social Services and Development',
            'Transportation and Communications',
            'Trade, Investment and Tourism',
        ];

        $agencies = [
            'Bangsamoro Parliament',
            'Preservation of Cultural Heritage',
            "Bangsamoro Darul-Ifta'",
            'Human Rights Commission',
            'Information and Communications Technology',
            'Planning and Development Authority',
            'Ports Management Authority',
            'Women Commission',
            'Youth Commission',
            'Sports Commission',
            'Development Academy of the Bangsamoro',
            'Cooperative and Social Enterprise',
            'Pilgrimage Authority',
            'Bangsamoro Information Office',
        ];

        foreach ($ministries as $i => $name) {
            DB::table('footer_links')->insert([
                'group' => 'ministry',
                'name' => $name,
                'url' => '#',
                'sort_order' => $i,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        foreach ($agencies as $i => $name) {
            DB::table('footer_links')->insert([
                'group' => 'agency',
                'name' => $name,
                'url' => '#',
                'sort_order' => $i,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('footer_links');
    }
};
