<?php

namespace App\Http\Controllers;

use App\Models\Entrada;
use Illuminate\Http\Request;

class EntradaController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function entradas()
    {
        $entradas = Entrada::with([
            'user.asociado',
            'user.adherente',
            'user.familiar',
            'user.empleado'
        ])->get();
        return response()->json($entradas, 200);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function crearEntrada($id)
    {
        Entrada::create([
            "user_id" => $id,
        ]);
        return response()->json([
            "status" => true,
            'message' => 'Entrada creada',
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Entrada $entrada)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Entrada $entrada)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Entrada $entrada)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Entrada $entrada)
    {
        //
    }
}
