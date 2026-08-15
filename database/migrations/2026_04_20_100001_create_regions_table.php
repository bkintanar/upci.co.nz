<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('regions', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('slug')->unique();
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });

        DB::table('regions')->insert([
            ['name' => 'North Region',   'slug' => 'north',   'sort_order' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Central Region', 'slug' => 'central', 'sort_order' => 2, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'South Region',   'slug' => 'south',   'sort_order' => 3, 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('regions');
    }
};
