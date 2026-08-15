<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('users')
            ->whereNull('access_level')
            ->whereNotNull('church_id')
            ->whereIn('role', ['pastor', 'senior_pastor', 'assistant_pastor'])
            ->update(['access_level' => 'local']);
    }

    public function down(): void
    {
        DB::table('users')
            ->whereIn('role', ['pastor', 'senior_pastor', 'assistant_pastor'])
            ->whereNotNull('church_id')
            ->where('access_level', 'local')
            ->update(['access_level' => null]);
    }
};
