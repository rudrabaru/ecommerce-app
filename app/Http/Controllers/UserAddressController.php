<?php

namespace App\Http\Controllers;

use App\Models\UserAddress;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class UserAddressController extends Controller
{
    public function index()
    {
        $addresses = Auth::user()->addresses()->where('type', 'shipping')->get();
        return view('addresses.index', ['addresses' => $addresses]);
    }

    public function create()
    {
        // Return empty form data for modal
        return response()->json([
            'success' => true,
            'address' => new UserAddress()
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'company' => 'nullable|string|max:255',
            'address_line_1' => 'required|string|max:255',
            'address_line_2' => 'nullable|string|max:255',
            'country_id' => 'required|exists:countries,id',
            'state_id' => 'required|exists:states,id',
            'city_id' => 'required|exists:cities,id',
            'postal_code' => 'required|string|max:20',
            'phone' => 'required|string|max:20',
            'country_code' => 'required|string|max:10',
            'email' => 'nullable|email|max:255',
            'is_default' => 'boolean',
        ]);

        $validated['user_id'] = Auth::id();
        $validated['type'] = 'shipping';

        $address = UserAddress::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Address created successfully.',
            'address' => $address->load(['country', 'state', 'city'])
        ]);
    }

    public function edit(UserAddress $address)
    {
        if ($address->user_id !== Auth::id()) {
            abort(403);
        }

        return response()->json([
            'success' => true,
            'address' => $address->load(['country', 'state', 'city'])
        ]);
    }

    public function update(Request $request, UserAddress $address)
    {
        if ($address->user_id !== Auth::id()) {
            abort(403);
        }

        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'company' => 'nullable|string|max:255',
            'address_line_1' => 'required|string|max:255',
            'address_line_2' => 'nullable|string|max:255',
            'country_id' => 'required|exists:countries,id',
            'state_id' => 'required|exists:states,id',
            'city_id' => 'required|exists:cities,id',
            'postal_code' => 'required|string|max:20',
            'phone' => 'required|string|max:20',
            'country_code' => 'required|string|max:10',
            'email' => 'nullable|email|max:255',
            'is_default' => 'boolean',
        ]);

        $address->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Address updated successfully.',
            'address' => $address->load(['country', 'state', 'city'])
        ]);
    }

    public function destroy(UserAddress $address)
    {
        if ($address->user_id !== Auth::id()) {
            abort(403);
        }

        $address->delete();

        return response()->json([
            'success' => true,
            'message' => 'Address deleted successfully.'
        ]);
    }

    public function setDefault(UserAddress $address)
    {
        if ($address->user_id !== Auth::id()) {
            abort(403);
        }

        $address->update(['is_default' => true]);

        return response()->json(['success' => true]);
    }
}
