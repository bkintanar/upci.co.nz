<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Database\Migrations\Migration;

/**
 * Four churches carried seeded placeholder pastor names that rendered on the
 * public locator. These are not missing data — they are false data, so a blank
 * field is the honest state and a placeholder string would not be.
 *
 * Only the exact seeded values are touched, so a real name entered since is
 * never clobbered. down() restores them verbatim: SQLite runs migrations
 * without a transaction, so a reversible down() is the only safety net.
 *
 * NOT included, deliberately: the stale `address` on Apostolics of
 * Christchurch ("789 Colombo Street"). The plan asked for it to be blanked,
 * but that church has no `street`/`suburb` values, so the legacy composite is
 * the only address it has — blanking it would empty the listing and degrade
 * the Google Maps directions link built from it at ChurchLocator.vue:328.
 * Correcting it is a client data task, not a deletion.
 */
return new class extends Migration
{
    private const PLACEHOLDERS = [
        'Southside Pentecostal Fellowship (SSPF)' => 'Pastor John Smith',
        'Apostolics of Christchurch (AOC)' => 'Pastor Michael Brown',
        'Grace Fellowship - A UPCINZ Church' => 'Pastor David Wilson',
        'Daystar Fellowship' => 'Pastor Robert Taylor',
    ];

    public function up(): void
    {
        foreach (self::PLACEHOLDERS as $church => $placeholder) {
            DB::table('churches')
                ->where('name', $church)
                ->where('pastor_name', $placeholder)
                ->update(['pastor_name' => null, 'updated_at' => now()]);
        }
    }

    public function down(): void
    {
        foreach (self::PLACEHOLDERS as $church => $placeholder) {
            DB::table('churches')
                ->where('name', $church)
                ->whereNull('pastor_name')
                ->update(['pastor_name' => $placeholder, 'updated_at' => now()]);
        }
    }
};
