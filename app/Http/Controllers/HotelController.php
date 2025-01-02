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
     *         @OA\JsonContent(
     *             required={"name", "address", "city", "nit", "max_rooms"},
     *             @OA\Property(property="name", type="string", example="Hotel Decameron"),
     *             @OA\Property(property="address", type="string", example="Calle 123 #45-67"),
     *             @OA\Property(property="city", type="string", example="Cartagena"),
     *             @OA\Property(property="nit", type="string", example="12345678-9"),
     *             @OA\Property(property="max_rooms", type="integer", example=50)
     *         )
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
