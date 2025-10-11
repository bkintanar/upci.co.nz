<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\Church;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Update existing service times data to new structure
        Church::whereNotNull('service_times')->chunk(100, function ($churches) {
            foreach ($churches as $church) {
                if (!$church->service_times) continue;

                $newServiceTimes = [];

                foreach ($church->service_times as $service) {
                    // Parse the old format: "Wednesday - Bible Study" -> service_type: "Bible Study", days: ["wednesday"]
                    $day = $service['day'] ?? '';
                    $time = $service['time'] ?? '';

                    if (empty($day) || empty($time)) continue;

                    // Extract day and service type from old format
                    $parts = explode(' - ', $day);
                    if (count($parts) === 2) {
                        $dayName = strtolower(trim($parts[0]));
                        $serviceType = trim($parts[1]);

                        // Map day names to our format
                        $dayMapping = [
                            'monday' => 'monday',
                            'tuesday' => 'tuesday',
                            'wednesday' => 'wednesday',
                            'thursday' => 'thursday',
                            'friday' => 'friday',
                            'saturday' => 'saturday',
                            'sunday' => 'sunday',
                        ];

                        if (isset($dayMapping[$dayName])) {
                            $newServiceTimes[] = [
                                'service_type' => $serviceType,
                                'time' => $time,
                                'days' => [$dayMapping[$dayName]],
                            ];
                        }
                    }
                }

                if (!empty($newServiceTimes)) {
                    $church->update(['service_times' => $newServiceTimes]);
                }
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Convert back to old format if needed
        Church::whereNotNull('service_times')->chunk(100, function ($churches) {
            foreach ($churches as $church) {
                if (!$church->service_times) continue;

                $oldServiceTimes = [];

                foreach ($church->service_times as $service) {
                    $serviceType = $service['service_type'] ?? 'Service';
                    $time = $service['time'] ?? '';
                    $days = $service['days'] ?? [];

                    foreach ($days as $day) {
                        $dayLabels = [
                            'monday' => 'Monday',
                            'tuesday' => 'Tuesday',
                            'wednesday' => 'Wednesday',
                            'thursday' => 'Thursday',
                            'friday' => 'Friday',
                            'saturday' => 'Saturday',
                            'sunday' => 'Sunday',
                        ];

                        $dayLabel = $dayLabels[$day] ?? ucfirst($day);
                        $oldServiceTimes[] = [
                            'day' => $dayLabel . ' - ' . $serviceType,
                            'time' => $time,
                        ];
                    }
                }

                if (!empty($oldServiceTimes)) {
                    $church->update(['service_times' => $oldServiceTimes]);
                }
            }
        });
    }
};
