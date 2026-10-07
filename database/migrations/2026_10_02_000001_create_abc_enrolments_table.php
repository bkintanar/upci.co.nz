<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

/**
 * First-time student enrolments for the Apostolic Bible College, replacing
 * the Google Form at forms.gle/iqVTh9YQH3nhZdw48. Columns follow that form's
 * sections in order; the transcript upload is new (the form asked for one
 * but had nowhere to put it).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('abc_enrolments', function (Blueprint $table) {
            $table->id();

            // Section 1. Personal information
            $table->string('full_name');
            $table->date('date_of_birth');
            $table->string('gender', 10);
            $table->string('citizenship');
            $table->text('address');
            $table->string('home_phone', 50);
            $table->string('mobile_phone', 50);
            $table->string('email');
            $table->boolean('resides_in_nz');
            $table->string('residence_country')->nullable();
            $table->text('reason_for_attending');

            // Section 2. Previous Bible school attendance
            $table->boolean('attended_upci_bible_school');
            $table->string('transcript_path')->nullable();
            $table->string('transcript_original_name')->nullable();

            // Section 3. Learning challenges
            $table->boolean('has_learning_difficulties');
            $table->string('learning_difficulty', 50)->nullable();
            $table->string('learning_difficulty_other')->nullable();

            // Section 4. Spiritual information
            $table->boolean('wants_ministerial_licence');
            $table->boolean('read_articles_of_faith');
            $table->boolean('baptised_in_jesus_name');
            $table->boolean('received_holy_ghost');
            $table->string('church_name');
            $table->string('church_name_other')->nullable();
            $table->string('pastor_name');
            $table->string('attendance_duration');
            $table->text('ministry_involvement');

            // Agreement
            $table->string('agreement_name');
            $table->date('agreement_date');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('abc_enrolments');
    }
};
