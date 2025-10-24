<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('churches', function (Blueprint $table) {
            // Add suburb field
            $table->string('suburb')->nullable()->after('street');

            // Remove state field
            $table->dropColumn('state');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('churches', function (Blueprint $table) {
            // Add state field back
            $table->string('state')->nullable()->after('city');

            // Remove suburb field
            $table->dropColumn('suburb');
        });
    }
};
