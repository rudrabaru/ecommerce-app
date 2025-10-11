<?php

namespace Database\Seeders;

use App\Models\Country;
use App\Models\State;
use Illuminate\Database\Seeder;

class StateSeeder extends Seeder
{
    public function run(): void
    {
        // United States States
        $us = Country::where('iso_code', 'US')->first();
        if ($us) {
            $usStates = [
                ['name' => 'Alabama', 'code' => 'AL'],
                ['name' => 'Alaska', 'code' => 'AK'],
                ['name' => 'Arizona', 'code' => 'AZ'],
                ['name' => 'Arkansas', 'code' => 'AR'],
                ['name' => 'California', 'code' => 'CA'],
                ['name' => 'Colorado', 'code' => 'CO'],
                ['name' => 'Connecticut', 'code' => 'CT'],
                ['name' => 'Delaware', 'code' => 'DE'],
                ['name' => 'Florida', 'code' => 'FL'],
                ['name' => 'Georgia', 'code' => 'GA'],
                ['name' => 'Hawaii', 'code' => 'HI'],
                ['name' => 'Idaho', 'code' => 'ID'],
                ['name' => 'Illinois', 'code' => 'IL'],
                ['name' => 'Indiana', 'code' => 'IN'],
                ['name' => 'Iowa', 'code' => 'IA'],
                ['name' => 'Kansas', 'code' => 'KS'],
                ['name' => 'Kentucky', 'code' => 'KY'],
                ['name' => 'Louisiana', 'code' => 'LA'],
                ['name' => 'Maine', 'code' => 'ME'],
                ['name' => 'Maryland', 'code' => 'MD'],
                ['name' => 'Massachusetts', 'code' => 'MA'],
                ['name' => 'Michigan', 'code' => 'MI'],
                ['name' => 'Minnesota', 'code' => 'MN'],
                ['name' => 'Mississippi', 'code' => 'MS'],
                ['name' => 'Missouri', 'code' => 'MO'],
                ['name' => 'Montana', 'code' => 'MT'],
                ['name' => 'Nebraska', 'code' => 'NE'],
                ['name' => 'Nevada', 'code' => 'NV'],
                ['name' => 'New Hampshire', 'code' => 'NH'],
                ['name' => 'New Jersey', 'code' => 'NJ'],
                ['name' => 'New Mexico', 'code' => 'NM'],
                ['name' => 'New York', 'code' => 'NY'],
                ['name' => 'North Carolina', 'code' => 'NC'],
                ['name' => 'North Dakota', 'code' => 'ND'],
                ['name' => 'Ohio', 'code' => 'OH'],
                ['name' => 'Oklahoma', 'code' => 'OK'],
                ['name' => 'Oregon', 'code' => 'OR'],
                ['name' => 'Pennsylvania', 'code' => 'PA'],
                ['name' => 'Rhode Island', 'code' => 'RI'],
                ['name' => 'South Carolina', 'code' => 'SC'],
                ['name' => 'South Dakota', 'code' => 'SD'],
                ['name' => 'Tennessee', 'code' => 'TN'],
                ['name' => 'Texas', 'code' => 'TX'],
                ['name' => 'Utah', 'code' => 'UT'],
                ['name' => 'Vermont', 'code' => 'VT'],
                ['name' => 'Virginia', 'code' => 'VA'],
                ['name' => 'Washington', 'code' => 'WA'],
                ['name' => 'West Virginia', 'code' => 'WV'],
                ['name' => 'Wisconsin', 'code' => 'WI'],
                ['name' => 'Wyoming', 'code' => 'WY']
            ];

            foreach ($usStates as $state) {
                State::updateOrCreate(
                    ['country_id' => $us->id, 'code' => $state['code']],
                    array_merge($state, ['country_id' => $us->id])
                );
            }
        }

        // Indian States
        $india = Country::where('iso_code', 'IN')->first();
        if ($india) {
            $indianStates = [
                ['name' => 'Andhra Pradesh', 'code' => 'AP'],
                ['name' => 'Arunachal Pradesh', 'code' => 'AR'],
                ['name' => 'Assam', 'code' => 'AS'],
                ['name' => 'Bihar', 'code' => 'BR'],
                ['name' => 'Chhattisgarh', 'code' => 'CG'],
                ['name' => 'Goa', 'code' => 'GA'],
                ['name' => 'Gujarat', 'code' => 'GJ'],
                ['name' => 'Haryana', 'code' => 'HR'],
                ['name' => 'Himachal Pradesh', 'code' => 'HP'],
                ['name' => 'Jharkhand', 'code' => 'JH'],
                ['name' => 'Karnataka', 'code' => 'KA'],
                ['name' => 'Kerala', 'code' => 'KL'],
                ['name' => 'Madhya Pradesh', 'code' => 'MP'],
                ['name' => 'Maharashtra', 'code' => 'MH'],
                ['name' => 'Manipur', 'code' => 'MN'],
                ['name' => 'Meghalaya', 'code' => 'ML'],
                ['name' => 'Mizoram', 'code' => 'MZ'],
                ['name' => 'Nagaland', 'code' => 'NL'],
                ['name' => 'Odisha', 'code' => 'OR'],
                ['name' => 'Punjab', 'code' => 'PB'],
                ['name' => 'Rajasthan', 'code' => 'RJ'],
                ['name' => 'Sikkim', 'code' => 'SK'],
                ['name' => 'Tamil Nadu', 'code' => 'TN'],
                ['name' => 'Telangana', 'code' => 'TG'],
                ['name' => 'Tripura', 'code' => 'TR'],
                ['name' => 'Uttar Pradesh', 'code' => 'UP'],
                ['name' => 'Uttarakhand', 'code' => 'UK'],
                ['name' => 'West Bengal', 'code' => 'WB']
            ];

            foreach ($indianStates as $state) {
                State::updateOrCreate(
                    ['country_id' => $india->id, 'code' => $state['code']],
                    array_merge($state, ['country_id' => $india->id])
                );
            }
        }

        // UK States/Counties
        $uk = Country::where('iso_code', 'GB')->first();
        if ($uk) {
            $ukCounties = [
                ['name' => 'Greater London', 'code' => 'LND'],
                ['name' => 'Greater Manchester', 'code' => 'MAN'],
                ['name' => 'West Midlands', 'code' => 'WMD'],
                ['name' => 'West Yorkshire', 'code' => 'WYK'],
                ['name' => 'Kent', 'code' => 'KEN'],
                ['name' => 'Essex', 'code' => 'ESX'],
                ['name' => 'Hampshire', 'code' => 'HAM'],
                ['name' => 'Lancashire', 'code' => 'LAN'],
                ['name' => 'Merseyside', 'code' => 'MSY'],
                ['name' => 'Surrey', 'code' => 'SRY']
            ];

            foreach ($ukCounties as $county) {
                State::updateOrCreate(
                    ['country_id' => $uk->id, 'code' => $county['code']],
                    array_merge($county, ['country_id' => $uk->id])
                );
            }
        }

        // Canadian Provinces
        $canada = Country::where('iso_code', 'CA')->first();
        if ($canada) {
            $provinces = [
                ['name' => 'Ontario', 'code' => 'ON'],
                ['name' => 'Quebec', 'code' => 'QC'],
                ['name' => 'British Columbia', 'code' => 'BC'],
                ['name' => 'Alberta', 'code' => 'AB'],
                ['name' => 'Manitoba', 'code' => 'MB'],
                ['name' => 'Saskatchewan', 'code' => 'SK'],
                ['name' => 'Nova Scotia', 'code' => 'NS'],
                ['name' => 'New Brunswick', 'code' => 'NB'],
                ['name' => 'Newfoundland and Labrador', 'code' => 'NL'],
                ['name' => 'Prince Edward Island', 'code' => 'PE']
            ];

            foreach ($provinces as $province) {
                State::updateOrCreate(
                    ['country_id' => $canada->id, 'code' => $province['code']],
                    array_merge($province, ['country_id' => $canada->id])
                );
            }
        }

        // Australian States
        $australia = Country::where('iso_code', 'AU')->first();
        if ($australia) {
            $ausStates = [
                ['name' => 'New South Wales', 'code' => 'NSW'],
                ['name' => 'Victoria', 'code' => 'VIC'],
                ['name' => 'Queensland', 'code' => 'QLD'],
                ['name' => 'Western Australia', 'code' => 'WA'],
                ['name' => 'South Australia', 'code' => 'SA'],
                ['name' => 'Tasmania', 'code' => 'TAS'],
                ['name' => 'Australian Capital Territory', 'code' => 'ACT'],
                ['name' => 'Northern Territory', 'code' => 'NT']
            ];

            foreach ($ausStates as $state) {
                State::updateOrCreate(
                    ['country_id' => $australia->id, 'code' => $state['code']],
                    array_merge($state, ['country_id' => $australia->id])
                );
            }
        }

        // German States
        $germany = Country::where('iso_code', 'DE')->first();
        if ($germany) {
            $germanStates = [
                ['name' => 'Bavaria', 'code' => 'BY'],
                ['name' => 'North Rhine-Westphalia', 'code' => 'NW'],
                ['name' => 'Baden-Württemberg', 'code' => 'BW'],
                ['name' => 'Lower Saxony', 'code' => 'NI'],
                ['name' => 'Hesse', 'code' => 'HE'],
                ['name' => 'Berlin', 'code' => 'BE'],
                ['name' => 'Hamburg', 'code' => 'HH'],
                ['name' => 'Saxony', 'code' => 'SN'],
                ['name' => 'Brandenburg', 'code' => 'BB'],
                ['name' => 'Thuringia', 'code' => 'TH']
            ];

            foreach ($germanStates as $state) {
                State::updateOrCreate(
                    ['country_id' => $germany->id, 'code' => $state['code']],
                    array_merge($state, ['country_id' => $germany->id])
                );
            }
        }

        // French Regions
        $france = Country::where('iso_code', 'FR')->first();
        if ($france) {
            $frenchRegions = [
                ['name' => 'Île-de-France', 'code' => 'IDF'],
                ['name' => 'Auvergne-Rhône-Alpes', 'code' => 'ARA'],
                ['name' => 'Hauts-de-France', 'code' => 'HDF'],
                ['name' => 'Provence-Alpes-Côte d\'Azur', 'code' => 'PAC'],
                ['name' => 'Occitanie', 'code' => 'OCC'],
                ['name' => 'Nouvelle-Aquitaine', 'code' => 'NAQ'],
                ['name' => 'Grand Est', 'code' => 'GES'],
                ['name' => 'Pays de la Loire', 'code' => 'PDL'],
                ['name' => 'Bretagne', 'code' => 'BRE'],
                ['name' => 'Normandie', 'code' => 'NOR']
            ];

            foreach ($frenchRegions as $region) {
                State::updateOrCreate(
                    ['country_id' => $france->id, 'code' => $region['code']],
                    array_merge($region, ['country_id' => $france->id])
                );
            }
        }
    }
}