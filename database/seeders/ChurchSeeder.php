<?php

namespace Database\Seeders;

use App\Models\Church;
use Illuminate\Database\Seeder;

class ChurchSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $churches = [
            [
                'name' => 'Church Triumphant Wellington',
                'address' => '90 Churton Dr Churton Park School',
                'city' => 'Churton Park',
                'state' => 'Wellington',
                'region' => 'Wellington',
                'zip' => '6037',
                'country' => 'New Zealand',
                'latitude' => -41.208471,
                'longitude' => 174.808346,
                'phone' => '+64 4 234 5678',
                'email' => 'wellington@upci.org.nz',
                'website' => 'https://churchtriumphant.nz',
                'service_times' => [
                    ['day' => 'Wednesday - Bible Study', 'time' => '7:00 PM'],
                    ['day' => 'Saturday - Prayer Meeting', 'time' => '7:00 PM'],
                    ['day' => 'Sunday - Worship Service', 'time' => '3:00 PM']
                ],
                'pastor_name' => 'Rev. Andrew Kintanar',
                'description' => 'A vibrant UPCI church serving the Wellington community with love, faith, and fellowship.',
                'is_active' => true,
                'is_featured' => true,
            ],
            [
                'name' => 'Auckland UPCI',
                'address' => '123 Queen Street',
                'city' => 'Auckland',
                'state' => 'Auckland',
                'region' => 'Auckland',
                'zip' => '1010',
                'country' => 'New Zealand',
                'latitude' => -36.8485,
                'longitude' => 174.7633,
                'phone' => '+64 9 123 4567',
                'email' => 'auckland@upci.org.nz',
                'website' => 'https://auckland.upci.org.nz',
                'service_times' => [
                    ['day' => 'Sunday', 'time' => '10:00 AM'],
                    ['day' => 'Wednesday', 'time' => '7:00 PM']
                ],
                'pastor_name' => 'Pastor John Smith',
                'description' => 'Serving the Auckland community with powerful worship and biblical teaching.',
                'is_active' => true,
                'is_featured' => false,
            ],
            [
                'name' => 'Christchurch UPCI',
                'address' => '789 Colombo Street',
                'city' => 'Christchurch',
                'state' => 'Canterbury',
                'region' => 'Canterbury',
                'zip' => '8011',
                'country' => 'New Zealand',
                'latitude' => -43.5321,
                'longitude' => 172.6362,
                'phone' => '+64 3 345 6789',
                'email' => 'christchurch@upci.org.nz',
                'website' => 'https://christchurch.upci.org.nz',
                'service_times' => [
                    ['day' => 'Sunday', 'time' => '10:30 AM'],
                    ['day' => 'Thursday', 'time' => '7:00 PM']
                ],
                'pastor_name' => 'Pastor Michael Brown',
                'description' => 'Building strong Christian community in Christchurch through faith and service.',
                'is_active' => true,
                'is_featured' => false,
            ],
            [
                'name' => 'Hamilton UPCI',
                'address' => '321 Victoria Street',
                'city' => 'Hamilton',
                'state' => 'Waikato',
                'region' => 'Waikato',
                'zip' => '3204',
                'country' => 'New Zealand',
                'latitude' => -37.7870,
                'longitude' => 175.2793,
                'phone' => '+64 7 456 7890',
                'email' => 'hamilton@upci.org.nz',
                'website' => 'https://hamilton.upci.org.nz',
                'service_times' => [
                    ['day' => 'Sunday', 'time' => '11:00 AM'],
                    ['day' => 'Wednesday', 'time' => '6:30 PM']
                ],
                'pastor_name' => 'Pastor David Wilson',
                'description' => 'A growing UPCI church in the heart of Hamilton, Waikato.',
                'is_active' => true,
                'is_featured' => false,
            ],
            [
                'name' => 'Tauranga UPCI',
                'address' => '654 Cameron Road',
                'city' => 'Tauranga',
                'state' => 'Bay of Plenty',
                'region' => 'Bay of Plenty',
                'zip' => '3112',
                'country' => 'New Zealand',
                'latitude' => -37.6878,
                'longitude' => 176.1651,
                'phone' => '+64 7 567 8901',
                'email' => 'tauranga@upci.org.nz',
                'website' => 'https://tauranga.upci.org.nz',
                'service_times' => [
                    ['day' => 'Sunday', 'time' => '9:00 AM'],
                    ['day' => 'Friday', 'time' => '7:00 PM']
                ],
                'pastor_name' => 'Pastor Lisa Davis',
                'description' => 'Spreading the gospel in the beautiful Bay of Plenty region.',
                'is_active' => true,
                'is_featured' => false,
            ],
            [
                'name' => 'Dunedin UPCI',
                'address' => '987 George Street',
                'city' => 'Dunedin',
                'state' => 'Otago',
                'region' => 'Otago',
                'zip' => '9016',
                'country' => 'New Zealand',
                'latitude' => -45.8788,
                'longitude' => 170.5028,
                'phone' => '+64 3 678 9012',
                'email' => 'dunedin@upci.org.nz',
                'website' => 'https://dunedin.upci.org.nz',
                'service_times' => [
                    ['day' => 'Sunday', 'time' => '10:00 AM'],
                    ['day' => 'Monday', 'time' => '7:00 PM']
                ],
                'pastor_name' => 'Pastor Robert Taylor',
                'description' => 'Serving the southern community of Dunedin with faith and dedication.',
                'is_active' => true,
                'is_featured' => false,
            ],
        ];

        foreach ($churches as $churchData) {
            Church::create($churchData);
        }
    }
}
