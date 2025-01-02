<?php

namespace App\Http\Controllers;

use App\Models\Hotel;
use Illuminate\Http\Request;

/**
 * @OA\Info(
 *     title="Hoteles Decameron API",
 *     version="1.0.0",
 *     description="API RESTful para la gestión de hoteles y habitaciones."
 * )
 *
 * @OA\Tag(
 *     name="Hoteles",
 *     description="Operaciones relacionadas con los hoteles"
 * )
 *
 * @OA\Schema(
 *     schema="Hotel",
 *     type="object",
 *     title="Hotel",
 *     description="Esquema de un hotel",
 *     required={"name", "address", "city", "nit", "max_rooms"},
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="name", type="string", example="Hotel Decameron"),
 *     @OA\Property(property="address", type="string", example="Calle 123 #45-67"),
 *     @OA\Property(property="city", type="string", example="Cartagena"),
 *     @OA\Property(property="nit", type="string", example="12345678-9"),
 *     @OA\Property(property="max_rooms", type="integer", example=50),
 *     @OA\Property(property="created_at", type="string", format="date-time", example="2023-01-01T00:00:00Z"),
 *     @OA\Property(property="updated_at", type="string", format="date-time", example="2023-01-01T00:00:00Z"),
 * )
 */
class HotelController extends Controller
{
    /**
     * Listar todos los hoteles.
     *
     * @OA\Get(
     *     path="/hotels",
     *     tags={"Hoteles"},
     *     summary="Obtener lista de hoteles",
     *     description="Devuelve una lista de todos los hoteles con sus habitaciones asociadas.",
     *     @OA\Response(
     *         response=200,
     *         description="Lista de hoteles con habitaciones."
     *     )
     * )
     */
    public function index()
    {
        return Hotel::with('rooms')->get();
    }

    /**
     * Crear un nuevo hotel.
     *
     * @OA\Post(
     *     path="/hotels",
     *     tags={"Hoteles"},
     *     summary="Crear un hotel",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(ref="#/components/schemas/Hotel")
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Hotel creado exitosamente."
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

    /**
     * Obtener detalles de un hotel.
     *
     * @OA\Get(
     *     path="/hotels/{id}",
     *     tags={"Hoteles"},
     *     summary="Obtener detalles de un hotel",
     *     description="Devuelve los detalles de un hotel por su ID.",
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID del hotel",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Detalles del hotel.",
     *         @OA\JsonContent(ref="#/components/schemas/Hotel")
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Hotel no encontrado."
     *     )
     * )
     */
    public function show(Hotel $hotel)
    {
        return response()->json($hotel, 200);
    }

    /**
     * Actualizar un hotel.
     *
     * @OA\Put(
     *     path="/hotels/{id}",
     *     tags={"Hoteles"},
     *     summary="Actualizar un hotel",
     *     description="Actualiza los detalles de un hotel existente.",
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID del hotel",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(ref="#/components/schemas/Hotel")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Hotel actualizado exitosamente.",
     *         @OA\JsonContent(ref="#/components/schemas/Hotel")
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Error de validación."
     *     )
     * )
     */
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
        return response()->json($hotel, 200);
    }

    /**
     * Eliminar un hotel.
     *
     * @OA\Delete(
     *     path="/hotels/{id}",
     *     tags={"Hoteles"},
     *     summary="Eliminar un hotel",
     *     description="Elimina un hotel por ID.",
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID del hotel",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=204,
     *         description="Hotel eliminado exitosamente."
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Hotel no encontrado."
     *     )
     * )
     */
    public function destroy(Hotel $hotel)
    {
        $hotel->delete();
        return response()->noContent();
    }
}
