<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Tymon\JWTAuth\Facades\JWTAuth;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $token = JWTAuth::attempt([
            "Documento" => $request->Documento,
            "password" => $request->password
        ]);

        if (!empty($token)) {
            $user = JWTAuth::user();

            if ($user->Rol == 1 && $user->admin->Estado == 0) {
                return response()->json([
                    "status" => false,
                    "message" => "Inactivo"
                ]);
            }

            if ($user->Rol == 0 || $user->Rol == 1) {
                $usuario = $user->admin;
            } else if ($user->Rol == 2) {
                $usuario = $user->asociado;
            } else if ($user->Rol == 3) {
                $usuario = $user->adherente;
            } else if ($user->Rol == 4 || $user->Rol == 6) {
                $usuario = $user->empleado;
            } else if ($user->Rol == 5) {
                $usuario = $user->familiar;
            }

            return response()->json([
                "status" => true,
                "user" => $usuario,
                "credenciales" => $user,
                "token" => $token
            ]);
        }

        return response()->json([
            "status" => false,
            "message" => "Credenciales Invalidas"
        ]);
    }
}
