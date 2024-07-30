<?php

namespace App\Http\Controllers;

use App\Models\Preguntas;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class PreguntasController extends Controller
{

    public function crearPregunta(Request $request)
    {
        try {
            $validated = $request->validate([
                'encuesta_id' => 'required',
                'Pregunta' => 'required|string',
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'status' => false,
                'message' => 'Datos',
                'errors' => $e->errors(),
            ], 200);
        }

        $Pregunta = Preguntas::create($validated);
        return response()->json([
            "status" => true,
            "message" => "hecho",
            'Pregunta' => $Pregunta
        ], 201);
    }

    public function Preguntas($id)
    {
        return Preguntas::where('encuesta_id', $id)->get();
    }

    public function contPreguntas($id)
    {
        return Preguntas::where('encuesta_id', $id)->get()->count();
    }

    public function getPregunta(string $id)
    {
        return Preguntas::findOrFail($id);
    }

    public function actualizarPregunta(Request $request, string $id)
    {
        try {
            $validated = $request->validate([
                'encuesta_id' => 'required',
                'Pregunta' => 'required|string',
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'status' => false,
                'message' => 'Datos',
                'errors' => $e->errors(),
            ], 200);
        }
        $Pregunta = Preguntas::findOrFail($id);
        $Pregunta->update($validated);
        return response()->json([
            "status" => true,
            "message" => "hecho",
            'Pregunta' => $Pregunta
        ], 201);
    }

    public function borrarPregunta(string $id)
    {
        $Pregunta = Preguntas::findOrFail($id);
        $Pregunta->delete();
        return response()->json([
            "status" => true,
            "message" => "hecho",
            'Pregunta' => $Pregunta
        ], 200);
    }
}
