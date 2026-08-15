<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('access_level')->nullable()->after('role');
            $table->foreignId('region_id')
                ->nullable()
                ->after('access_level')
                ->constrained('regions')
                ->nullOnDelete();
        });

        DB::table('users')
            ->whereIn('role', ['executive_board', 'administrator'])
            ->update(['access_level' => 'national']);

        foreach (DB::table('users')->where('role', 'regional_presbyter')->get() as $u) {
            $region = DB::table('regions')->where('name', $u->assigned_region)->first();
            DB::table('users')->where('id', $u->id)->update([
                'access_level' => 'regional',
                'region_id' => $region?->id,
            ]);
        }

        DB::table('users')
            ->whereNull('access_level')
            ->whereNotNull('church_id')
            ->update(['access_level' => 'local']);

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('assigned_region');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('assigned_region')->nullable()->after('role');
        });

        foreach (DB::table('users')->whereNotNull('region_id')->get() as $u) {
            $name = DB::table('regions')->where('id', $u->region_id)->value('name');
            DB::table('users')->where('id', $u->id)->update(['assigned_region' => $name]);
        }

        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['region_id']);
            $table->dropColumn(['region_id', 'access_level']);
        });
    }
};
