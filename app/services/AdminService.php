<?php

namespace App\services;

use App\Models\Admin;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class AdminService
{

    protected $validationsService;

    public function __construct(ValidationsService $validationsService)
    {
        $this->validationsService = $validationsService;
    }

    public function validateAdmin($request)
    {
        $rules = [
            'Documento' => 'required|unique:users,Documento',
            'Correo' => 'required|email|unique:admins,Correo',
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
                'errors' => $validator->errors()->all()
            ];
        }

        return [
            'status' => true,
            'message' => 'Validación exitosa'
        ];
    }

    public function crearAdmin($request)
    {
        $validation = $this->validateAdmin($request);
        if (!$validation['status']) return $validation;
        DB::beginTransaction();
        try {
            $user = User::create([
                'Documento' => $request->Documento,
                'password' => Hash::make($request->Clave),
                'Rol' => $request->Rol
            ]);

            $admin = new Admin();
            $admin->user_id = $user->id;
            $admin->Nombre = $request->Nombre;
            $admin->Apellidos = $request->Apellidos;
            $admin->Correo = $request->Correo;
            $admin->Telefono = $request->Telefono;
            $admin->Estado = 1;
            $admin->save();

            DB::commit();

            return response()->json([
                "status" => true,
                "message" => "hecho"
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                "message" => "Error al actualizar: " . $e->getMessage()
            ], 500);
        }
    }

    public function getAdmins()
    {
        $admins = User::with('admin')->where('Rol', 1)->get();
        return response()->json($admins);
    }

    public function actualizarAdmin($request, $id)
    {
        DB::beginTransaction();
        try {
            $usuario = Admin::findOrFail($id);
            $usuario->update([
                "Nombre" => $request->Nombre,
                "Apellidos" => $request->Apellidos,
                "Correo" => $request->Correo,
                "Telefono" => $request->Telefono,
            ]);

            DB::commit();

            return response()->json([
                "status" => true,
                "message" => "hecho"
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                "status" => false,
                "message" => "Error al actualizar: " . $e->getMessage()
            ], 500);
        }
    }

    public function changeStatus($id)
    {
        DB::beginTransaction();
        try {
            $admin = Admin::findOrFail($id);
            $nuevoEstado = $admin->Estado == 0 ? 1 : 0;
            $admin->Estado = $nuevoEstado;
            $admin->save();

            DB::commit();
            return response()->json([
                "status" => true,
                "message" => "hecho"
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                "status" => false,
                "message" => "Error en el servidor: " . $e->getMessage()
            ], 500);
        }
    }

    public function eliminarAdmin($id)
    {
        $user = User::find($id);
        if (is_null($user)) {
            return response()->json([
                "status" => false,
                "message" => "no encontrado"
            ], 404);
        }
        $user->admin->delete();
        $user->delete();
        return response()->json([
            "status" => true,
            "message" => "hecho"
        ], 200);
    }
}
