<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $about = DB::table('menu_items')
            ->where('location', 'header')
            ->where('label', 'About the UPCI NZ')
            ->whereNull('parent_id')
            ->first();

        if ($about && ! DB::table('menu_items')->where('parent_id', $about->id)->where('label', 'AGS Updates')->exists()) {
            DB::table('menu_items')->insert([
                'label' => 'AGS Updates',
                'description' => 'Assistant General Superintendent\'s updates',
                'url' => '/ags-updates',
                'location' => 'header',
                'sort_order' => 6,
                'is_active' => true,
                'open_in_new_tab' => false,
                'parent_id' => $about->id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        DB::table('menu_items')
            ->where('label', 'AGS Updates')
            ->where('location', 'header')
            ->delete();
    }
};
