<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

/**
 * Single-row table for site-wide settings.
 *
 * Two logos, not one: the navbar is a horizontal strip that suits the stacked
 * lockup, while the footer has room for the horizontal one. They must be
 * editable independently.
 *
 * No spatie/laravel-settings here — Filament v4 has no SettingsPage in core
 * (it lives in a plugin this project does not have), so this is a plain model
 * driven by a custom page.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('site_settings', function (Blueprint $table) {
            $table->id();
            $table->string('header_logo_path')->nullable();
            $table->string('footer_logo_path')->nullable();
            $table->string('contact_email')->nullable();
            $table->text('footer_blurb')->nullable();
            $table->json('social_links')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('site_settings');
    }
};
