<?php

namespace App\services;

use App\Models\Adherente;
use Illuminate\Support\Facades\Validator;

class ValidationsService
{

    public function validatePersona($request)
    {
        $rules = [
            'Documento' => 'required|unique:users,Documento',
            'Correo' => 'required|email|unique:users,Correo',
        ];

        $messages = [
            'Documento.required' => 'El Documento es obligatorio.',
            'Documento.unique' => 'El Documento ya está registrado en el sistema.',
            'Correo.required' => 'El campo Correo es obligatorio.',
            'Correo.email' => 'El Correo no tiene un formato válido.',
            'Correo.unique' => 'El Correo ya está registrado en el sistema.',
        ];

        $validator = Validator::make($request->all(), $rules, $messages);

        if ($validator->fails()) {
            return [
                'status' => false,
                'errors' => $validator->errors()
            ];
        }

        return [
            'status' => true,
            'message' => 'Validación exitosa'
        ];
    }
}
