<?php

namespace App\Http\Controllers;

use App\Models\Rubros;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class RubrosController extends Controller
{

    public function crearRubro(Request $request)
    {
        try {
            $validated = $request->validate([
                'rubro' => 'required',
                'valor' => 'required',
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'status' => false,
                'message' => 'Datos',
                'errors' => $e->errors(),
            ], 200);
        }
        $rubro = Rubros::create($validated);
        return response()->json([
            'status' => true,
            'message' => 'Rubro creado',
            'data' => $rubro
        ]);
    }

    public function rubros()
    {
        $rubros = Rubros::all();
        return response()->json($rubros);
    }

    public function actualizarRubro(Request $request, string $id)
    {
        $rubro = Rubros::findOrFail($id);
        $rubro->update($request->all());
        return response()->json([
            'status' => true,
            'message' => 'Rubro actualizado',
            'data' => $rubro
        ]);
    }

    public function borrarRubro(string $id)
    {
        $rubro = Rubros::findOrFail($id);
        $rubro->delete();
        return response()->json([
            'status' => true,
            'message' => 'Rubro borrado',
            'data' => $rubro
        ]);
    }

}
