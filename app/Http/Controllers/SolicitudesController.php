<?php

namespace App\Http\Controllers;

use App\Models\Solicitudes;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class SolicitudesController extends Controller
{

    public function crearSolicitud(Request $request)
    {
        $validatedData = $request->validate([
            'Tipo' => 'required|string',
            'Descripcion' => 'required|string',
            'user_id' => 'required|integer|exists:users,id',
            'Estado' => 'required'
        ]);

        try {
            $solicitud = Solicitudes::create($validatedData);
            return response()->json([
                "status" => true,
                "message" => "hecho",
                'solicitud' => $solicitud
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                "status" => false,
                'message' => 'Error al crear la solicitud',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function solicitudes()
    {
        $solicitudes = Solicitudes::with(['user.asociado', 'user.adherente'])
            ->orderBy('created_at', 'desc')->get();
        return response()->json($solicitudes);
    }

    public function solicitud($id)
    {
        $solicitudes = Solicitudes::find($id);
        return response()->json($solicitudes);
    }

    public function getSolicitudUser($id)
    {
        $solicitudes = Solicitudes::where('user_id', $id)->orderBy('created_at', 'desc')->get();
        return response()->json($solicitudes);
    }

    public function contSolicitudesPendientes()
    {
        $solicitudes = Solicitudes::where('Estado', 1)->get()->count();
        return response()->json($solicitudes);
    }

    public function contSolicitudesUser($id)
    {
        $solicitudes = Solicitudes::where('user_id', $id)->get()->count();
        return response()->json($solicitudes);
    }

    public function responderSolicitud(Request $request, $id)
    {
        try {
            $request->validate(['Respuesta' => 'required|string']);
        } catch (ValidationException $e) {
            return response()->json([
                'status' => false,
                'message' => 'Validate',
                'errors' => $e->errors(),
            ], 200);
        }
        try {
            $solicitud = Solicitudes::find($id);
            $res = $solicitud->update([
                'Respuesta' => $request->Respuesta,
                'Estado' => 0
            ]);
            if ($res) {
                return response()->json([
                    "status" => true,
                    "message" => "hecho"
                ], 201);
            } else {
                response()->json([
                    "status" => false,
                    "message" => "No se pudo agregar"
                ],);
            }
        } catch (\Exception $e) {
            return response()->json([
                "message" => "Error al responder: " . $e->getMessage()
            ], 500);
        }
    }
}
