<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('churches', function (Blueprint $table) {
            $table->foreignId('region_id')
                ->nullable()
                ->after('organizational_region')
                ->constrained('regions')
                ->nullOnDelete();
        });

        foreach (DB::table('regions')->get() as $region) {
            DB::table('churches')
                ->where('organizational_region', $region->name)
                ->update(['region_id' => $region->id]);
        }

        Schema::table('churches', function (Blueprint $table) {
            $table->dropColumn('organizational_region');
        });
    }

    public function down(): void
    {
        Schema::table('churches', function (Blueprint $table) {
            $table->string('organizational_region')->nullable();
        });

        foreach (DB::table('regions')->get() as $region) {
            DB::table('churches')
                ->where('region_id', $region->id)
                ->update(['organizational_region' => $region->name]);
        }

        Schema::table('churches', function (Blueprint $table) {
            $table->dropForeign(['region_id']);
            $table->dropColumn('region_id');
        });
    }
};
