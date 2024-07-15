<?php

namespace App\Http\Controllers;

use App\Mail\EstadosMail;
use App\Models\Adherente;
use App\Models\Asociado;
use App\Models\Empleado;
use App\Models\Familiar;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

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

    public function sendResetCode(Request $request)
    {
        try {
            $request->validate([
                'Documento' => 'required|exists:users,Documento',
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'status' => false,
                'message' => 'No existe',
                'errors' => $e->errors(),
            ], 200);
        }

        $user = User::where('Documento', $request->Documento)->first();
        if ($user->Rol == 2) {
            $usuario = Asociado::where('Documento', $user->Documento)->first();
        } else if ($user->Rol == 3) {
            $usuario = Adherente::where('Documento', $user->Documento)->first();
        } else if ($user->Rol == 4 || $user->Rol == 6) {
            $usuario = Empleado::where('Documento', $user->Documento)->first();
        } else if ($user->Rol == 5) {
            $usuario = Familiar::where('Documento', $user->Documento)->first();
        }

        $resetToken = Str::random(32);
        $expiresAt = Carbon::now()->addHour();

        DB::table('password_reset_tokens')->updateOrInsert(
            ['email' => $usuario->Documento],
            ['token' => $resetToken, 'created_at' => Carbon::now()]
        );

        $fecha = now()->format('d/m/Y');
        $content =
            <<<HTML
            <h1>Club Sincelejo</h1>
            <p><strong>Fecha:</strong> {$fecha}</p>
            <h3>Cordial saludo,</h3>
            <p>Queremos informarle que hemos recibido una solicitud para recuperar tu contraseña.</p>
            <p>Por favor, utiliza el siguiente código para restablecer tu contraseña:</p>
            <p><strong>Código de recuperación:</strong> {$resetToken}</p>
            <p>Si no solicitaste esto, por favor ignora este correo y tu contraseña permanecerá sin cambios.</p>
            <p>En caso de inquietudes, no dudes en contactar a la gerencia del club.</p>
            <p>Atentamente,<br>
            Gerencia<br>
            Club Sincelejo</p>
            HTML;
        Mail::to($usuario->Correo)->send(new EstadosMail($content));

        return response()->json([
            'status' => true,
            'message' => 'Correo de restablecimiento enviado'
        ]);
    }

    public function validateResetCode(Request $request)
    {
        $request->validate([
            'code' => 'required|string',
        ]);

        $passwordReset = DB::table('password_reset_tokens')
            ->where('token', $request->code)
            ->first();

        if (!$passwordReset || Carbon::parse($passwordReset->created_at)->addHour()->isPast()) {
            return response()->json([
                'status' => false,
                'message' => 'Código inválido'
            ], 200);
        }

        return response()->json([
            'status' => true,
            'message' => 'Código válido'
        ], 200);
    }

    public function resetPassword(Request $request)
    {
        $request->validate([
            'code' => 'required|string',
            'new_password' => 'required|string|min:8',
        ]);

        $passwordReset = DB::table('password_reset_tokens')
            ->where('token', $request->code)
            ->first();

        if (!$passwordReset || Carbon::parse($passwordReset->created_at)->addHour()->isPast()) {
            return response()->json([
                'status' => false,
                'message' => 'Código inválido'
            ], 400);
        }

        $user = User::where('Documento', $passwordReset->email)->first();
        $user->password = Hash::make($request->new_password);
        $user->save();

        DB::table('password_reset_tokens')->where('email', $passwordReset->email)->delete();

        return response()->json([
            'status' => true,
            'message' => 'Contraseña cambiada exitosamente'
        ]);
    }
}
