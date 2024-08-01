<?php

namespace App\Http\Controllers;

use App\Models\Adherente;
use App\Models\Asociado;
use App\Models\Encuestas;
use App\Models\Preguntas;
use App\Models\RespuestasUsuario;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class EncuestasController extends Controller
{

    public function crearEncuesta(Request $request)
    {
        try {
            $validated = $request->validate([
                'Titulo' => 'required|string',
                'Descripcion' => 'required|string',
                'Estado' => 'required',
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'status' => false,
                'message' => 'Datos',
                'errors' => $e->errors(),
            ], 200);
        }

        $encuesta = Encuestas::create($validated);
        return response()->json([
            "status" => true,
            "message" => "hecho",
            'encuesta' => $encuesta
        ], 201);
    }

    public function encuestas()
    {
        $encuestas = Encuestas::withCount('preguntas')->orderBy('created_at', 'desc')->get();
        return response()->json($encuestas);
    }

    public function getEncuestaConRespuestas($id)
    {
        $encuesta = Encuestas::findOrFail($id);
        $preguntas = Preguntas::where('encuesta_id', $id)->get();
        $respuestasUsuarios = RespuestasUsuario::whereIn('pregunta_id', $preguntas->pluck('id'))
            ->orderBy('created_at', 'asc')
            ->get();
        $usuariosRespuestas = $respuestasUsuarios->groupBy('user_id');
        $response = $usuariosRespuestas->map(function ($respuestas, $userId) {
            $user = User::find($userId);
            $userInfo = null;
            if ($user->Rol == 2) {
                $userInfo = Asociado::where('user_id', $userId)
                    ->select('id', 'Nombre', 'Apellidos', 'Imagen', 'user_id')
                    ->first();
            } elseif ($user->Rol == 3) {
                $userInfo = Adherente::where('user_id', $userId)
                    ->select('id', 'Nombre', 'Apellidos', 'Imagen', 'user_id')
                    ->first();
            }
            $fechaRespuesta = $respuestas->first()->created_at->toDateTimeString();
            return [
                'user_info' => $userInfo,
                'fecha_respuesta' => $fechaRespuesta,
                'respuestas' => $respuestas->map(function ($respuesta) {
                    return [
                        'pregunta' => $respuesta->pregunta->Pregunta,
                        'respuesta' => $respuesta->respuesta->Respuesta,
                    ];
                })
            ];
        })->values();
        return response()->json($response);
    }

    public function encuestasDisponibles($id)
    {
        User::findOrFail($id);
        $respondidas = RespuestasUsuario::where('user_id', $id)
            ->pluck('pregunta_id')
            ->unique();

        $encuestasRespondidas = Preguntas::whereIn('id', $respondidas)
            ->pluck('encuesta_id')
            ->unique();

        $encuestasNoRespondidas = Encuestas::whereNotIn('id', $encuestasRespondidas)->get();

        return response()->json($encuestasNoRespondidas);
    }

    public function contEncuestas()
    {
        return Encuestas::all()->count();
    }

    public function getEncuesta(string $id)
    {
        $encuesta = Encuestas::with(['preguntas.respuestas'])->find($id);
        return response()->json($encuesta);
    }

    public function actualizarEncuesta(Request $request, string $id)
    {
        try {
            $validated = $request->validate([
                'Titulo' => 'required|string',
                'Descripcion' => 'required|string',
                'Estado' => 'required',
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'status' => false,
                'message' => 'Datos',
                'errors' => $e->errors(),
            ], 200);
        }
        $encuesta = Encuestas::findOrFail($id);
        $encuesta->update($validated);
        return response()->json([
            "status" => true,
            "message" => "hecho",
            'encuesta' => $encuesta
        ], 201);
    }

    public function borrarEncuesta(string $id)
    {
        $encuesta = Encuestas::findOrFail($id);
        $encuesta->delete();
        return response()->json([
            "status" => true,
            "message" => "hecho",
            'encuesta' => $encuesta
        ], 200);
    }
}
