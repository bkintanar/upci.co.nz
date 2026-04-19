<?php

namespace Database\Seeders;

use App\Models\Event;
use Illuminate\Database\Seeder;

class EventSeeder extends Seeder
{
    public function run(): void
    {
        Event::firstOrCreate(
            ['slug' => 'general-conference'],
            [
                'name' => 'General Conference',
                'description' => 'UPCI New Zealand General Conference.',
                'start_date' => now()->year . '-01-15',
                'end_date' => null,
                'location' => null,
                'url' => null,
                'is_published' => true,
                'sort_order' => 1,
            ]
        );

        Event::firstOrCreate(
            ['slug' => 'annual-ministers-meeting'],
            [
                'name' => 'Annual Minister\'s Meeting',
                'description' => 'Annual Minister\'s Meeting for UPCI NZ ministers.',
                'start_date' => now()->year . '-03-01',
                'end_date' => null,
                'location' => null,
                'url' => null,
                'is_published' => true,
                'sort_order' => 2,
            ]
        );
    }
}
