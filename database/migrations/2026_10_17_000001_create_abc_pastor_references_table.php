<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

/**
 * The pastor's reference for a Bible college applicant (owner request,
 * 2026-10-17, from Brandon Borders' draft questionnaire).
 *
 * When someone enrols, the pastor they name is emailed a link to a short
 * questionnaire: faithful in attendance, holiness & modesty, and tithes;
 * serving, and how; approval, and whether they are happy with the level of
 * study. The answers land here and are shown on the enrolment in the admin.
 *
 * One row per enrolment (unique abc_enrolment_id). Resending the email
 * rotates the token on the same row rather than adding another.
 *
 * `token_hash` is the sha256 of the emailed link's token, never the token
 * itself: a copy of the database must not hand anyone a working link. A
 * random token rather than a signed URL, because the link opens a page in
 * the Vue app, not a Laravel route, and must survive an APP_KEY change.
 *
 * `pastor_email_source` says where the address came from (the church's
 * records in the CMS, the applicant, or an administrator), so the college
 * can tell a pastor's own address from one an applicant typed in.
 *
 * Answers are nullable until `submitted_at` is set; a reference is answered
 * once.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('abc_pastor_references', function (Blueprint $table) {
            $table->id();
            $table->foreignId('abc_enrolment_id')->unique()->constrained()->cascadeOnDelete();

            $table->string('pastor_name');
            $table->string('pastor_email');
            $table->string('pastor_email_source', 20);
            $table->string('token_hash', 64)->unique();
            $table->timestamp('emailed_at')->nullable();

            // 1. Is this applicant faithful in the following?
            $table->boolean('faithful_attendance')->nullable();
            $table->boolean('faithful_holiness')->nullable();
            $table->boolean('faithful_tithes')->nullable();
            // 2. Serving in some area of the church, and how.
            $table->boolean('is_serving')->nullable();
            $table->text('serving_details')->nullable();
            // 3. Approval, and the level of study.
            $table->boolean('approves')->nullable();
            $table->boolean('approves_level')->nullable();
            $table->text('comments')->nullable();
            $table->string('signed_name')->nullable();
            $table->timestamp('submitted_at')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('abc_pastor_references');
    }
};
