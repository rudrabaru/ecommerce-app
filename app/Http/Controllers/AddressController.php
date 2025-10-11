<?php

namespace App\Http\Controllers;

use App\Models\UserAddress;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Support\Facades\Auth;

class AddressController extends Controller
{
    use AuthorizesRequests, ValidatesRequests;
    public function index(): JsonResponse
    {
        $addresses = Auth::user()->addresses()
            ->with(['country', 'state', 'city'])
            ->get();
            
        return response()->json(['data' => $addresses]);
    }

    public function store(Request $request): JsonResponse
    {
        $validatedData = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'phone' => 'required|string|max:20',
            'country_code' => 'required|string|max:10',
            'email' => 'nullable|email|max:255',
            'country_id' => 'required|exists:countries,id',
            'state_id' => 'required|exists:states,id',
            'city_id' => 'required|exists:cities,id',
            'postal_code' => 'required|string|max:20',
            'address_line_1' => 'required|string|max:255',
            'address_line_2' => 'nullable|string|max:255',
            'company' => 'nullable|string|max:255',
            'is_default' => 'boolean'
        ]);

        $address = Auth::user()->addresses()->create($validatedData);

        if ($request->input('is_default')) {
            Auth::user()->addresses()
                ->where('id', '!=', $address->id)
                ->update(['is_default' => false]);
        }

        return response()->json([
            'message' => 'Address created successfully',
            'data' => $address->load(['country', 'state', 'city'])
        ]);
    }

    public function show(UserAddress $address): JsonResponse
    {
        $this->authorize('view', $address);
        
        return response()->json([
            'data' => $address->load(['country', 'state', 'city'])
        ]);
    }

    public function update(Request $request, UserAddress $address): JsonResponse
    {
        $this->authorize('update', $address);
        
        $validatedData = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'phone' => 'required|string|max:20',
            'country_code' => 'required|string|max:10',
            'email' => 'nullable|email|max:255',
            'country_id' => 'required|exists:countries,id',
            'state_id' => 'required|exists:states,id',
            'city_id' => 'required|exists:cities,id',
            'postal_code' => 'required|string|max:20',
            'address_line_1' => 'required|string|max:255',
            'address_line_2' => 'nullable|string|max:255',
            'company' => 'nullable|string|max:255',
            'is_default' => 'boolean'
        ]);

        $address->update($validatedData);

        if ($request->input('is_default')) {
            auth()->user()->addresses()
                ->where('id', '!=', $address->id)
                ->update(['is_default' => false]);
        }

        return response()->json([
            'message' => 'Address updated successfully',
            'data' => $address->load(['country', 'state', 'city'])
        ]);
    }

    public function destroy(UserAddress $address): JsonResponse
    {
        $this->authorize('delete', $address);
        
        if ($address->is_default) {
            return response()->json([
                'message' => 'Cannot delete default address'
            ], 422);
        }

        $address->delete();

        return response()->json([
            'message' => 'Address deleted successfully'
        ]);
    }
}