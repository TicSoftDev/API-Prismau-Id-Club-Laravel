<?php

namespace App\Http\Controllers;

use App\Mail\EstadosMail;
use App\Models\User;
use App\services\UserService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

class UsuarioController extends Controller
{

    protected $userService;

    public function __construct(UserService $userService)
    {
        $this->userService = $userService;
    }

    public function consultarSociosConValores()
    {
        return $this->userService->getSociosConPrecios();
    }

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
            $usuario = null;
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
                    if ($familiar->adherente) {
                        $familiar->familiares = $familiar->adherente->familiares;
                    } elseif ($familiar->asociado) {
                        $familiar->familiares = $familiar->asociado->familiares;
                    }
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

    public function resetearPassword(String $id){
        return $this->userService->resetPassword($id);
    }

    public function eliminarCuenta(String $id)
    {
        $user = User::find($id);
        if ($user->Rol == 2) {
            $email = $user->asociado->Correo;
        } else if ($user->Rol == 3) {
            $email = $user->adherente->Correo;
        }
        $fecha = now()->format('d/m/Y');
        $content = <<<HTML
                        <h1>Club Sincelejo</h1>
                        <p><strong>Fecha:</strong> {$fecha}</p>
                        <h3>Cordial saludo,</h3>
                        <p>Queremos informarle que hemos recibido su solicitud para eliminación de cuenta en los proximos dias estaremos comunicandonos nuevamente con usted.</p>
                        <p>Gracias.</p>
                        HTML;
        Mail::to($email)->send(new EstadosMail($content, null));
        return response()->json([
            "status" => true,
            "message" => "hecho"
        ]);
    }
}
