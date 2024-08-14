<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UsuarioController extends Controller
{

    public function filtroUsuarios()
    {
        $users = User::with(['asociado', 'adherente', 'empleado', 'familiar'])->get();

        $filteredUsers = $users->map(function ($user) {
            $info = $user->asociado ?? $user->adherente ?? $user->empleado ?? $user->familiar;
            return [
                'id' => $user->id,
                'imagen' => $info->imagen ?? null,
                'nombre' => $info ? $info->Nombre : null,
                'apellidos' => $info ? $info->Apellidos : null,
                'tipoDocumento' => $info ? $info->TipoDocumento : null,
                'documento' => $info ? $info->Documento : null,
                'codigo' => $info ? $info->Codigo : null,
                'estado' => $info ? $info->Estado : null,
                'rol' => $user->Rol
            ];
        });
        return response()->json($filteredUsers);
    }

    public function buscarUsuario(String $documento)
    {
        $user = User::with([
            'admin',
            'asociado',
            'adherente',
            'empleado',
            'familiar',
            'familiar.adherente',
            'familiar.asociado'
        ])->where('Documento', $documento)->first();
        if ($user) {
            if ($user->Rol == 0 || $user->Rol == 1) {
                $usuario =  $user->admin;
            } else if ($user->Rol == 2) {
                $usuario =  $user->asociado;
                $usuario->familiar = $user->asociado->familiares;
            } else if ($user->Rol == 3) {
                $usuario =  $user->adherente;
                $usuario->familiar = $user->adherente->familiares;
            } else if ($user->Rol == 4 || $user->Rol == 6) {
                $usuario =  $user->empleado;
            } else if ($user->Rol == 5) {
                $familiar = $user->familiar;
                if ($familiar) {
                    $familiar['relacionado'] = $familiar->adherente ?? $familiar->asociado;
                    $usuario = $familiar;
                }
            }
            return response()->json([
                "status" => true,
                "user" => $usuario,
                "credenciales" => $user,
            ]);
        } else {
            return response()->json([
                "status" => false,
            ]);
        }
    }

    public function cambiarPassword(Request $request, String $id)
    {
        $usuario = User::find($id);
        $usuario->password = Hash::make($request->password);
        $usuario->save();

        return response()->json([
            "message" => "hecho"
        ], 201);
    }
}
