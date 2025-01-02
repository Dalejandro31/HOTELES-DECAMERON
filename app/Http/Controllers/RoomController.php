<?php

namespace App\Http\Controllers;

use App\Models\Room;
use App\Models\Hotel;
use Illuminate\Http\Request;

/**
 * @OA\Tag(
 *     name="Habitaciones",
 *     description="Operaciones relacionadas con las habitaciones"
 * )
 */

class RoomController extends Controller
{
    /**
     * Listar todas las habitaciones.
     *
     * @OA\Get(
     *     path="/rooms",
     *     tags={"Habitaciones"},
     *     summary="Obtener lista de habitaciones",
     *     description="Devuelve una lista de todas las habitaciones junto con el hotel al que pertenecen.",
     *     @OA\Response(
     *         response=200,
     *         description="Lista de habitaciones."
     *     )
     * )
     */
    public function index()
    {
        return Room::with('hotel')->get();
    }

        /**
     * Crear una nueva habitación.
     *
     * @OA\Post(
     *     path="/rooms",
     *     tags={"Habitaciones"},
     *     summary="Crear una habitación",
     *     description="Crea una nueva habitación asociada a un hotel.",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"hotel_id", "type", "accommodation", "quantity"},
     *             @OA\Property(property="hotel_id", type="integer", example=1),
     *             @OA\Property(property="type", type="string", example="Standard"),
     *             @OA\Property(property="accommodation", type="string", example="Doble"),
     *             @OA\Property(property="quantity", type="integer", example=10)
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Habitación creada exitosamente."
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Error de validación."
     *     )
     * )
     */
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

    /**
     * Mostrar detalles de una habitación específica.
     *
     * @OA\Get(
     *     path="/rooms/{id}",
     *     tags={"Habitaciones"},
     *     summary="Obtener detalles de una habitación",
     *     description="Devuelve los detalles de una habitación específica, incluyendo el hotel asociado.",
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID de la habitación",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Detalles de la habitación."
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Habitación no encontrada."
     *     )
     * )
     */

    public function show(Room $room)
    {
        return $room->load('hotel');
    }

     /**
     * Actualizar una habitación existente.
     *
     * @OA\Put(
     *     path="/rooms/{id}",
     *     tags={"Habitaciones"},
     *     summary="Actualizar una habitación",
     *     description="Actualiza los detalles de una habitación específica.",
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID de la habitación",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"type", "accommodation", "quantity"},
     *             @OA\Property(property="type", type="string", example="Junior"),
     *             @OA\Property(property="accommodation", type="string", example="Triple"),
     *             @OA\Property(property="quantity", type="integer", example=5)
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Habitación actualizada exitosamente."
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Error de validación."
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Habitación no encontrada."
     *     )
     * )
     */

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

     /**
     * Eliminar una habitación.
     *
     * @OA\Delete(
     *     path="/rooms/{id}",
     *     tags={"Habitaciones"},
     *     summary="Eliminar una habitación",
     *     description="Elimina una habitación específica por su ID.",
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID de la habitación",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=204,
     *         description="Habitación eliminada exitosamente."
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Habitación no encontrada."
     *     )
     * )
     */

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
