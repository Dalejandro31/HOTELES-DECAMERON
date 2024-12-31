<?php

namespace App\Http\Controllers;

use App\Models\Hotel;
use Illuminate\Http\Request;

class HotelController extends Controller
{
    public function index()
    {
        return Hotel::with('rooms')->get();
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|unique:hotels,name',
            'address' => 'required',
            'city' => 'required',
            'nit' => 'required|unique:hotels,nit',
            'max_rooms' => 'required|integer|min:1',
        ], [
            'name.unique' => 'El nombre del hotel ya está en uso.',
            'nit.unique' => 'El NIT ya está registrado para otro hotel.',
        ]);

        try {
            return Hotel::create($validated);
        } catch (\Exception $e) {
            return response()->json(['error' => 'No se pudo crear el hotel.'], 500);
        }
    }

    public function show(Hotel $hotel)
    {
        return $hotel->load('rooms');
    }

    public function update(Request $request, Hotel $hotel)
    {
        $validated = $request->validate([
            'name' => 'required|unique:hotels,name,' . $hotel->id,
            'address' => 'required',
            'city' => 'required',
            'nit' => 'required|unique:hotels,nit,' . $hotel->id,
            'max_rooms' => 'required|integer',
        ]);

        $hotel->update($validated);
        return $hotel;
    }

    public function destroy(Hotel $hotel)
    {
        $hotel->delete();
        return response()->noContent();
    }
}
