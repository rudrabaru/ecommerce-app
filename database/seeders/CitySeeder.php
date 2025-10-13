<?php

namespace Database\Seeders;

use App\Models\State;
use App\Models\City;
use Illuminate\Database\Seeder;

class CitySeeder extends Seeder
{
    public function run(): void
    {
        // Indian Cities - Maharashtra
        $maharashtra = State::where('code', 'MH')->first();
        if ($maharashtra) {
            $maharashtraCities = [
                'Mumbai', 'Pune', 'Nagpur', 'Thane', 'Nashik',
                'Aurangabad', 'Solapur', 'Jalgaon', 'Amravati', 'Kolhapur'
            ];

            foreach ($maharashtraCities as $city) {
                City::updateOrCreate(
                    ['state_id' => $maharashtra->id, 'name' => $city],
                    ['state_id' => $maharashtra->id, 'name' => $city]
                );
            }
        }

        // Indian Cities - Gujarat
        $gujarat = State::where('code', 'GJ')->first();
        if ($gujarat) {
            $gujaratCities = [
                'Ahmedabad', 'Surat', 'Vadodara', 'Rajkot', 'Bhavnagar',
                'Jamnagar', 'Junagadh', 'Gandhinagar', 'Anand', 'Nadiad'
            ];

            foreach ($gujaratCities as $city) {
                City::updateOrCreate(
                    ['state_id' => $gujarat->id, 'name' => $city],
                    ['state_id' => $gujarat->id, 'name' => $city]
                );
            }
        }

        // US Cities - California
        $california = State::where('code', 'CA')->first();
        if ($california) {
            $californiaCities = [
                'Los Angeles', 'San Francisco', 'San Diego', 'San Jose', 'Sacramento',
                'Long Beach', 'Oakland', 'Bakersfield', 'Anaheim', 'Santa Ana'
            ];

            foreach ($californiaCities as $city) {
                City::updateOrCreate(
                    ['state_id' => $california->id, 'name' => $city],
                    ['state_id' => $california->id, 'name' => $city]
                );
            }
        }

        // US Cities - New York
        $newYork = State::where('code', 'NY')->first();
        if ($newYork) {
            $newYorkCities = [
                'New York City', 'Buffalo', 'Rochester', 'Yonkers', 'Syracuse',
                'Albany', 'New Rochelle', 'Mount Vernon', 'Schenectady', 'Utica'
            ];

            foreach ($newYorkCities as $city) {
                City::updateOrCreate(
                    ['state_id' => $newYork->id, 'name' => $city],
                    ['state_id' => $newYork->id, 'name' => $city]
                );
            }
        }

        // UK Cities - Greater London
        $london = State::where('code', 'LND')->first();
        if ($london) {
            $londonCities = [
                'City of London', 'Westminster', 'Camden', 'Greenwich', 'Hackney',
                'Hammersmith', 'Islington', 'Kensington', 'Lambeth', 'Southwark'
            ];

            foreach ($londonCities as $city) {
                City::updateOrCreate(
                    ['state_id' => $london->id, 'name' => $city],
                    ['state_id' => $london->id, 'name' => $city]
                );
            }
        }

        // Canadian Cities - Ontario
        $ontario = State::where('code', 'ON')->first();
        if ($ontario) {
            $ontarioCities = [
                'Toronto', 'Ottawa', 'Mississauga', 'Hamilton', 'London',
                'Brampton', 'Windsor', 'Markham', 'Vaughan', 'Kitchener'
            ];

            foreach ($ontarioCities as $city) {
                City::updateOrCreate(
                    ['state_id' => $ontario->id, 'name' => $city],
                    ['state_id' => $ontario->id, 'name' => $city]
                );
            }
        }

        // Australian Cities - New South Wales
        $nsw = State::where('code', 'NSW')->first();
        if ($nsw) {
            $nswCities = [
                'Sydney', 'Newcastle', 'Wollongong', 'Central Coast', 'Wagga Wagga',
                'Albury', 'Orange', 'Dubbo', 'Tamworth', 'Port Macquarie'
            ];

            foreach ($nswCities as $city) {
                City::updateOrCreate(
                    ['state_id' => $nsw->id, 'name' => $city],
                    ['state_id' => $nsw->id, 'name' => $city]
                );
            }
        }

        // German Cities - Bavaria
        $bavaria = State::where('code', 'BY')->first();
        if ($bavaria) {
            $bavarianCities = [
                'Munich', 'Nuremberg', 'Augsburg', 'Regensburg', 'Würzburg',
                'Ingolstadt', 'Fürth', 'Erlangen', 'Bayreuth', 'Bamberg'
            ];

            foreach ($bavarianCities as $city) {
                City::updateOrCreate(
                    ['state_id' => $bavaria->id, 'name' => $city],
                    ['state_id' => $bavaria->id, 'name' => $city]
                );
            }
        }

        // French Cities - Île-de-France
        $idf = State::where('code', 'IDF')->first();
        if ($idf) {
            $idfCities = [
                'Paris', 'Boulogne-Billancourt', 'Saint-Denis', 'Versailles', 'Argenteuil',
                'Montreuil', 'Nanterre', 'Créteil', 'Courbevoie', 'Rueil-Malmaison'
            ];

            foreach ($idfCities as $city) {
                City::updateOrCreate(
                    ['state_id' => $idf->id, 'name' => $city],
                    ['state_id' => $idf->id, 'name' => $city]
                );
            }
        }
    }
}
