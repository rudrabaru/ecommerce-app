<?php

namespace Database\Seeders;

use App\Models\Country;
use Illuminate\Database\Seeder;

class CountrySeeder extends Seeder
{
    public function run(): void
    {
        $countries = [
            ['name' => 'Afghanistan', 'iso_code' => 'AF', 'phone_code' => '+93'],
            ['name' => 'Albania', 'iso_code' => 'AL', 'phone_code' => '+355'],
            ['name' => 'Algeria', 'iso_code' => 'DZ', 'phone_code' => '+213'],
            ['name' => 'Andorra', 'iso_code' => 'AD', 'phone_code' => '+376'],
            ['name' => 'Angola', 'iso_code' => 'AO', 'phone_code' => '+244'],
            ['name' => 'Argentina', 'iso_code' => 'AR', 'phone_code' => '+54'],
            ['name' => 'Australia', 'iso_code' => 'AU', 'phone_code' => '+61'],
            ['name' => 'Austria', 'iso_code' => 'AT', 'phone_code' => '+43'],
            ['name' => 'Azerbaijan', 'iso_code' => 'AZ', 'phone_code' => '+994'],
            ['name' => 'Bahamas', 'iso_code' => 'BS', 'phone_code' => '+1242'],
            ['name' => 'Bahrain', 'iso_code' => 'BH', 'phone_code' => '+973'],
            ['name' => 'Bangladesh', 'iso_code' => 'BD', 'phone_code' => '+880'],
            ['name' => 'Belgium', 'iso_code' => 'BE', 'phone_code' => '+32'],
            ['name' => 'Brazil', 'iso_code' => 'BR', 'phone_code' => '+55'],
            ['name' => 'Canada', 'iso_code' => 'CA', 'phone_code' => '+1'],
            ['name' => 'China', 'iso_code' => 'CN', 'phone_code' => '+86'],
            ['name' => 'Denmark', 'iso_code' => 'DK', 'phone_code' => '+45'],
            ['name' => 'Egypt', 'iso_code' => 'EG', 'phone_code' => '+20'],
            ['name' => 'France', 'iso_code' => 'FR', 'phone_code' => '+33'],
            ['name' => 'Germany', 'iso_code' => 'DE', 'phone_code' => '+49'],
            ['name' => 'Greece', 'iso_code' => 'GR', 'phone_code' => '+30'],
            ['name' => 'India', 'iso_code' => 'IN', 'phone_code' => '+91'],
            ['name' => 'Indonesia', 'iso_code' => 'ID', 'phone_code' => '+62'],
            ['name' => 'Iran', 'iso_code' => 'IR', 'phone_code' => '+98'],
            ['name' => 'Iraq', 'iso_code' => 'IQ', 'phone_code' => '+964'],
            ['name' => 'Ireland', 'iso_code' => 'IE', 'phone_code' => '+353'],
            ['name' => 'Italy', 'iso_code' => 'IT', 'phone_code' => '+39'],
            ['name' => 'Japan', 'iso_code' => 'JP', 'phone_code' => '+81'],
            ['name' => 'Mexico', 'iso_code' => 'MX', 'phone_code' => '+52'],
            ['name' => 'Netherlands', 'iso_code' => 'NL', 'phone_code' => '+31'],
            ['name' => 'New Zealand', 'iso_code' => 'NZ', 'phone_code' => '+64'],
            ['name' => 'Norway', 'iso_code' => 'NO', 'phone_code' => '+47'],
            ['name' => 'Pakistan', 'iso_code' => 'PK', 'phone_code' => '+92'],
            ['name' => 'Poland', 'iso_code' => 'PL', 'phone_code' => '+48'],
            ['name' => 'Portugal', 'iso_code' => 'PT', 'phone_code' => '+351'],
            ['name' => 'Qatar', 'iso_code' => 'QA', 'phone_code' => '+974'],
            ['name' => 'Russia', 'iso_code' => 'RU', 'phone_code' => '+7'],
            ['name' => 'Saudi Arabia', 'iso_code' => 'SA', 'phone_code' => '+966'],
            ['name' => 'Singapore', 'iso_code' => 'SG', 'phone_code' => '+65'],
            ['name' => 'South Africa', 'iso_code' => 'ZA', 'phone_code' => '+27'],
            ['name' => 'South Korea', 'iso_code' => 'KR', 'phone_code' => '+82'],
            ['name' => 'Spain', 'iso_code' => 'ES', 'phone_code' => '+34'],
            ['name' => 'Sweden', 'iso_code' => 'SE', 'phone_code' => '+46'],
            ['name' => 'Switzerland', 'iso_code' => 'CH', 'phone_code' => '+41'],
            ['name' => 'Thailand', 'iso_code' => 'TH', 'phone_code' => '+66'],
            ['name' => 'Turkey', 'iso_code' => 'TR', 'phone_code' => '+90'],
            ['name' => 'Ukraine', 'iso_code' => 'UA', 'phone_code' => '+380'],
            ['name' => 'United Arab Emirates', 'iso_code' => 'AE', 'phone_code' => '+971'],
            ['name' => 'United Kingdom', 'iso_code' => 'GB', 'phone_code' => '+44'],
            ['name' => 'United States', 'iso_code' => 'US', 'phone_code' => '+1'],
            ['name' => 'Vietnam', 'iso_code' => 'VN', 'phone_code' => '+84'],
        ];

        foreach ($countries as $country) {
            Country::updateOrCreate(
                ['iso_code' => $country['iso_code']],
                $country
            );
        }
    }
}