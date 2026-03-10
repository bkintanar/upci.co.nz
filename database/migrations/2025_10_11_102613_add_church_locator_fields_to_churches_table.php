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
            // Location fields for mapping
            $table->decimal('latitude', 10, 8)->nullable()->after('country');
            $table->decimal('longitude', 11, 8)->nullable()->after('latitude');
            $table->string('region')->nullable()->after('state'); // For New Zealand regions

            // Service information
            $table->json('service_times')->nullable()->after('youtube'); // Store service times as JSON

            // Pastor information
            $table->string('pastor_name')->nullable()->after('service_times');

            // Additional contact information
            $table->text('description')->nullable()->after('pastor_name');

            // Status and visibility
            $table->boolean('is_active')->default(true)->after('description');
            $table->boolean('is_featured')->default(false)->after('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('churches', function (Blueprint $table) {
            $table->dropColumn([
                'latitude',
                'longitude',
                'region',
                'service_times',
                'pastor_name',
                'description',
                'is_active',
                'is_featured'
            ]);
        });
    }
};
