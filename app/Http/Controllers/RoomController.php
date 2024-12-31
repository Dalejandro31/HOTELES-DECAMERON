<?php

namespace App\Http\Controllers;

use App\Models\Room;
use App\Models\Hotel;
use Illuminate\Http\Request;

class RoomController extends Controller
{
    public function index()
    {
        return Room::with('hotel')->get();
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'hotel_id' => 'required|exists:hotels,id',
            'type' => 'required|in:Standard,Junior,Suite',
            'accommodation' => 'required',
            'quantity' => 'required|integer|min:1',
        ]);

        // Validar las acomodaciones según el tipo de habitación
        $this->validateAccommodation($validated['type'], $validated['accommodation']);

        // Validar que no exceda el número máximo de habitaciones
        $hotel = Hotel::findOrFail($validated['hotel_id']);
        $totalRooms = $hotel->rooms->sum('quantity') + $validated['quantity'];

        if ($totalRooms > $hotel->max_rooms) {
            return response()->json([
                'error' => 'El número total de habitaciones excede la capacidad máxima del hotel.'
            ], 422);
        }

        // Validar que no haya tipos/acomodaciones duplicadas para el mismo hotel
        if ($hotel->rooms()->where('type', $validated['type'])->where('accommodation', $validated['accommodation'])->exists()) {
            return response()->json([
                'error' => 'Ya existe este tipo de habitación con la misma acomodación en este hotel.'
            ], 422);
        }

        $room = Room::create($validated);
        return response()->json($room, 201);
    }

    public function show(Room $room)
    {
        return $room->load('hotel');
    }

    public function update(Request $request, Room $room)
    {
        $validated = $request->validate([
            'type' => 'required|in:Standard,Junior,Suite',
            'accommodation' => 'required',
            'quantity' => 'required|integer|min:1',
        ]);

        // Validar las acomodaciones según el tipo de habitación
        $this->validateAccommodation($validated['type'], $validated['accommodation']);

        $hotel = $room->hotel;

        // Validar que no exceda el número máximo de habitaciones
        $totalRooms = $hotel->rooms->sum('quantity') - $room->quantity + $validated['quantity'];

        if ($totalRooms > $hotel->max_rooms) {
            return response()->json([
                'error' => 'El número total de habitaciones excede la capacidad máxima del hotel.'
            ], 422);
        }

        $room->update($validated);
        return $room;
    }

    public function destroy(Room $room)
    {
        $room->delete();
        return response()->noContent();
    }

    private function validateAccommodation($type, $accommodation)
    {
        $validAccommodations = [
            'Standard' => ['Sencilla', 'Doble'],
            'Junior' => ['Triple', 'Cuadruple'],
            'Suite' => ['Sencilla', 'Doble', 'Triple'],
        ];

        // Verifica si el tipo tiene las acomodaciones definidas y si la acomodación es válida
        if (!isset($validAccommodations[$type]) || !in_array($accommodation, $validAccommodations[$type])) {
            abort(422, 'La acomodación seleccionada no es válida para este tipo de habitación.');
        }
    }

}
