<?php

namespace App\Http\Controllers;

use App\Http\Requests\PersonalRequest;
use App\Models\Admin;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class AdminsController extends Controller
{

    public function crearAdmin(PersonalRequest $request)
    {
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

    public function admins()
    {
        $admins = User::with('admin')->where('Rol', 1)->get();
        return response()->json($admins);
    }

    public function contAdmins()
    {
        $admins = User::with('admin')->where('Rol', 1)->count();
        return response()->json($admins);
    }

    public function actualizarAdmin(Request $request, string $id)
    {
        DB::beginTransaction();
        try {
            $usuario = User::findOrFail($id);
            $usuario->admin->update([
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
                "message" => "Error al actualizar: " . $e->getMessage()
            ], 500);
        }
    }

    public function changeStatus(String $id)
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

    public function eliminarAdmin(string $id)
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
