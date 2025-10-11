<?php

namespace Modules\Location\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Country;
use App\Models\State;
use App\Models\City;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class LocationController extends Controller
{
    public function countries(): JsonResponse
    {
        try {
            $countries = Country::select('id', 'name', 'iso_code')->get();
            return response()->json($countries);
        } catch (\Exception $e) {
            Log::error('Error fetching countries: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to fetch countries'], 500);
        }
    }

    public function states($country): JsonResponse
    {
        try {
            $states = State::where('country_id', $country)
                ->select('id', 'name')
                ->get();
            return response()->json($states);
        } catch (\Exception $e) {
            Log::error('Error fetching states: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to fetch states'], 500);
        }
    }

    public function cities($state): JsonResponse
    {
        try {
            $cities = City::where('state_id', $state)
                ->select('id', 'name')
                ->get();
            return response()->json($cities);
        } catch (\Exception $e) {
            Log::error('Error fetching cities: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to fetch cities'], 500);
        }
    }

    public function phoneCodes(): JsonResponse
    {
        try {
            $countries = Country::select('phone_code', 'iso_code')->get();
            return response()->json($countries);
        } catch (\Exception $e) {
            Log::error('Error fetching phone codes: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to fetch phone codes'], 500);
        }
    }
}