<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('churches', function (Blueprint $table) {
            $table->string('organizational_region')->nullable()->after('region');
            $table->string('church_status')->nullable()->after('organizational_region');
            $table->boolean('potential_home_group')->default(false)->after('church_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('churches', function (Blueprint $table) {
            $table->dropColumn(['organizational_region', 'church_status', 'potential_home_group']);
        });
    }
};
