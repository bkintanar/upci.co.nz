<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->foreignId('department_id')
                ->nullable()
                ->after('url')
                ->constrained('departments')
                ->nullOnDelete();
            $table->index('department_id');
        });
    }

    public function down(): void
    {
        Schema::table('events', function (Blueprint $table) {
            // The standalone index above is separate from the one ->constrained()
            // creates, and it outlives the column drop. On SQLite that leaves an
            // index pointing at a column that no longer exists, and the whole
            // statement aborts with "no such column: department_id".
            //
            // This down() had never been executed, so the defect was latent
            // until migrate:reset was run on a scratch database.
            $table->dropIndex(['department_id']);
            $table->dropConstrainedForeignId('department_id');
        });
    }
};
