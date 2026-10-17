<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

/**
 * Which page a contact message was sent from (owner request, 2026-10-17).
 *
 * The contact form is now a CMS block. It sits on /connect-with-us and on the
 * Apostolic Bible College's own Connect page, and both send to the same
 * inbox. Without this the college could not tell its messages from everyone
 * else's.
 *
 * Nullable: messages sent before this, and any sent without a page, have no
 * source.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('contact_messages', function (Blueprint $table) {
            $table->string('source_page')->nullable()->after('message');
        });
    }

    public function down(): void
    {
        Schema::table('contact_messages', function (Blueprint $table) {
            $table->dropColumn('source_page');
        });
    }
};
