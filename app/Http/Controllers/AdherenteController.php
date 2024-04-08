<?php

namespace App\Http\Controllers;

use App\Models\Estados;
use App\Models\Personal;
use App\Models\User;
use Illuminate\Http\Request;

class AdherenteController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $adherentes = User::with('personal')
            ->where('Rol', 3)
            ->whereHas('personal', function ($query) {
                $query->where('Estado', 1);
            })
            ->get();
        return response()->json($adherentes);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $adherentes = User::where('Rol', '3')->count();
        return response()->json($adherentes);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show()
    {
        $adherentes = User::with('personal')
            ->where('Rol', 3)
            ->whereHas('personal', function ($query) {
                $query->where('Estado', 0);
            })
            ->get();
        return response()->json($adherentes);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }

    public function changeStatus(String $id, Request $request)
    {
        $usuario = Personal::find($id);
        if (is_null($usuario)) {
            return response()->json(["message" => "no encontrado"], 404);
        }
        if ($usuario->Estado == "0") {
            $estado = 1;
            $estadoString = "Activo";
        } else {
            $estado = 0;
            $estadoString = "Inactivo";
        }
        $usuario->Estado = $estado;
        $usuario->save();

        $usuario->familiares()->update(['estado' => $estado]);

        Estados::create([
            'personal_id' => $id,
            'Estado' => $estadoString,
            'Motivo' => $request->Motivo
        ]);
        
        return response()->json([
            "message" => "hecho"
        ], 201);
    }

    public function changeToAsociado(String $id)
    {
        $usuario = User::find($id);
        if (is_null($usuario)) {
            return response()->json(["message" => "no encontrado"], 404);
        }
        $usuario->update([
            'Rol' => 2,
        ]);
        $res = $usuario->personal->update([
            'asociado_id' => null,
        ]);

        if ($res > 0) {
            return response()->json([
                "message" => "hecho"
            ], 201);
        } else {
            response()->json([
                "message" => "No se pudo agregar"
            ],);
        }
    }
}
