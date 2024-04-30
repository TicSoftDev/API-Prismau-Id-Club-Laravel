<?php

namespace App\Http\Controllers;

use App\Models\Estados;
use App\Models\Personal;
use App\Models\User;
use Illuminate\Http\Request;

class AsociadoController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $adherentes = User::with(['personal' => function ($query) {
            $query->withCount('familiares');
        }])
            ->where('Rol', 2)
            ->whereHas('personal', function ($query) {
                $query->where('Estado', 1);
            })
            ->get()
            ->sortBy('personal.Nombre');

        return response()->json($adherentes->values()->all());
    }



    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $asociados = User::where('Rol', '2')->count();
        return response()->json($asociados);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
    }

    /**
     * Display the specified resource.
     */
    public function show()
    {
        $adherentes = User::with(['personal' => function ($query) {
            $query->withCount('familiares');
        }])
            ->where('Rol', 2)
            ->whereHas('personal', function ($query) {
                $query->where('Estado', 0);
            })
            ->get()
            ->sortBy('personal.Nombre');

        return response()->json($adherentes->values()->all());
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
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
    }

    public function changeStatus(String $id, Request $request)
    {
        $asociado = Personal::with('familiares')->findOrFail($id);
        if ($asociado->Estado == 0) {
            $estado = 1;
            $estadoString = "Activo";
        } else {
            $estado = 0;
            $estadoString = "Inactivo";
        }
        $asociado->Estado = $estado;
        $asociado->save();

        foreach ($asociado->familiares as $familiar) {
            $familiar->estado = $estado; // Inactivar los familiares del asociado
            $familiar->save();
        }

        $adherentes = Personal::with('familiares')->where('asociado_id', $id)->get();
        foreach ($adherentes as $adherente) {
            $adherente->Estado = $estado; // Inactivar el adherente
            $adherente->save();

            foreach ($adherente->familiares as $familiar) {
                $familiar->Estado = $estado; // Inactivar los familiares del adherente
                $familiar->save();
            }
        }

        Estados::create([
            'personal_id' => $id,
            'Estado' => $estadoString,
            'Motivo' => $request->Motivo
        ]);

        return response()->json([
            "message" => "hecho"
        ], 201);
    }

    public function changeToAdherente(String $id)
    {
        $usuario = User::find($id);
        if (is_null($usuario)) {
            return response()->json(["message" => "no encontrado"], 404);
        }
        $usuario->update([
            'Rol' => 3,
        ]);
        $adherente = Personal::where('asociado_id', $usuario->personal->id);
        if (!is_null($adherente)) {
            $adherente->update([
                'asociado_id' => null,
            ]);
        }
        return response()->json([
            "message" => "hecho"
        ], 201);
    }
}
