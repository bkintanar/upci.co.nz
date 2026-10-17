<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

/**
 * A phone number on contact messages (owner request, 2026-10-17: "add a phone
 * number in the get in touch form").
 *
 * Optional, so someone who would rather not give one can still write.
 * Nullable for the same reason, and because earlier messages have none.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('contact_messages', function (Blueprint $table) {
            $table->string('phone', 50)->nullable()->after('email');
        });
    }

    public function down(): void
    {
        Schema::table('contact_messages', function (Blueprint $table) {
            $table->dropColumn('phone');
        });
    }
};
