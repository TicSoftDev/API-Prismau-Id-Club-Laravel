<?php

namespace App\Http\Controllers;

use App\Models\DisponibilidadEspacio;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class DisponibilidadEspacioController extends Controller
{

    public function crearDisponibilidad(Request $request)
    {
        try {
            $validated = $request->validate([
                'espacio_id' => 'required|exists:espacios,id',
                'Dia' => 'required|in:Lunes,Martes,Miércoles,Jueves,Viernes,Sábado,Domingo',
                'Inicio' => 'required|date_format:H:i',
                'Fin' => 'required|date_format:H:i|after:Inicio',
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'status' => false,
                'message' => 'Datos',
                'errors' => $e->errors(),
            ], 200);
        }

        $existeDisponibilidad = DisponibilidadEspacio::where('espacio_id', $validated['espacio_id'])
            ->where('Dia', $validated['Dia'])
            ->where(function ($query) use ($validated) {
                $query->whereBetween('Inicio', [$validated['Inicio'], $validated['Fin']])
                    ->orWhereBetween('Fin', [$validated['Inicio'], $validated['Fin']])
                    ->orWhere(function ($query) use ($validated) {
                        $query->where('Inicio', '<=', $validated['Inicio'])
                            ->where('Fin', '>=', $validated['Fin']);
                    });
            })->exists();

        if ($existeDisponibilidad) {
            return response()->json([
                'status' => false,
                'message' => 'Existe',
            ], 200);
        }

        $disponibilidad = DisponibilidadEspacio::create($validated);
        return response()->json([
            'status' => true,
            'message' => 'hecho',
            'data' => $disponibilidad
        ], 201);
    }

    public function getDisponibilidadesEspacio($id)
    {
        $disponibilidades = DisponibilidadEspacio::where('espacio_id', $id)
            ->orderByRaw("FIELD(Dia, 'Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado', 'Domingo')")
            ->orderBy('Inicio', 'asc')->get();

        return response()->json($disponibilidades);
    }


    public function updateDisponibilidad(Request $request, $id)
    {
        try {
            $validated = $request->validate([
                'espacio_id' => 'required|exists:espacios,id',
                'Dia' => 'required|in:Lunes,Martes,Miércoles,Jueves,Viernes,Sábado,Domingo',
                'Inicio' => 'required|date_format:H:i',
                'Fin' => 'required|date_format:H:i|after:Inicio',
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'status' => false,
                'message' => 'Datos',
                'errors' => $e->errors(),
            ], 200);
        }

        $disponibilidad = DisponibilidadEspacio::findOrFail($id);

        $existeDisponibilidad = DisponibilidadEspacio::where('espacio_id', $validated['espacio_id'])
            ->where('Dia', $validated['Dia'])
            ->where('id', '!=', $id)
            ->where(function ($query) use ($validated) {
                $query->whereBetween('Inicio', [$validated['Inicio'], $validated['Fin']])
                    ->orWhereBetween('Fin', [$validated['Inicio'], $validated['Fin']])
                    ->orWhere(function ($query) use ($validated) {
                        $query->where('Inicio', '<=', $validated['Inicio'])
                            ->where('Fin', '>=', $validated['Fin']);
                    });
            })->exists();

        if ($existeDisponibilidad) {
            return response()->json([
                'status' => false,
                'message' => 'Existe',
            ], 200);
        }

        $disponibilidad->update($validated);

        return response()->json([
            'status' => true,
            'message' => 'Disponibilidad actualizada con éxito.',
            'data' => $disponibilidad
        ], 200);
    }

    public function eliminarDisponibilidad($id)
    {
        try {
            $disponibilidad = DisponibilidadEspacio::findOrFail($id);
            $disponibilidad->delete();

            return response()->json([
                'status' => true,
                'message' => 'Disponibilidad eliminada con éxito.',
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Error al eliminar disponibilidad.',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
