<?php

namespace App\Http\Controllers;

use App\Models\Entrada;
use Illuminate\Http\Request;

class EntradaController extends Controller
{
    
    public function entradas()
    {
        $entradas = Entrada::with([
            'user.asociado',
            'user.adherente',
            'user.familiar',
            'user.empleado'
        ])->orderBy('created_at', 'desc')->get();
        return response()->json($entradas, 200);
    }

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

}
