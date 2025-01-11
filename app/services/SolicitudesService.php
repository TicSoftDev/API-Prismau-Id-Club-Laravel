<?php

namespace App\services;

use App\Models\Solicitudes;
use Illuminate\Support\Facades\Validator;

class SolicitudesService
{

    public function crearSolicitud($request)
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
            ->orderBy('Estado', 'desc')->get();
        return response()->json($solicitudes);
    }

    public function solicitud($id)
    {
        $solicitudes = Solicitudes::find($id);
        return response()->json($solicitudes);
    }

    public function getSolicitudUser($id)
    {
        $solicitudes = Solicitudes::where('user_id', $id)->orderBy('Estado', 'desc')->get();
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

    public function validarRespuesta($request)
    {
        $rules = [
            'Respuesta' => 'required|string',
        ];

        $messages = [
            'Respuesta.required' => 'La Respuesta es obligatoria.',
        ];

        $validator = Validator::make($request->all(), $rules, $messages);

        if ($validator->fails()) {
            return [
                'status' => false,
                'errors' => $validator->errors()->all()
            ];
        }

        return [
            'status' => true,
            'message' => 'Validación exitosa'
        ];
    }

    public function responderSolicitud($request, $id)
    {
        try {
            $validation = $this->validarRespuesta($request);
            if (!$validation['status']) return $validation;
            $solicitud = Solicitudes::find($id);
            $solicitud->update([
                'Respuesta' => $request->Respuesta,
                'Estado' => 0
            ]);
            return response()->json([
                "status" => true,
                "message" => "hecho"
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                "message" => "Error al responder: " . $e->getMessage()
            ], 500);
        }
    }
}
