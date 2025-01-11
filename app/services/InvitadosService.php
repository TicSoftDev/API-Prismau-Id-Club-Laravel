<?php

namespace App\services;

use App\Models\Invitado;
use App\Models\User;
use Carbon\Carbon;

class InvitadosService
{

    public function crearInvitacion($request)
    {
        $cantidad = $this->verificarLimiteMensual($request->Documento);
        if ($cantidad >= 2) {
            return response()->json([
                'status' => false,
                'message' => 'Supero el limite de invitaciones'
            ], 200);
        }
        $estadoSocio = $this->verificarEstadoUsuario($request->Documento);
        if ($estadoSocio == "false") {
            return response()->json([
                'status' => false,
                'message' => 'No se puede invitar a un socio moroso.'
            ]);
        }
        $user = $this->obtenerUsuario($request->user_id);
        $usuarioInfo = $this->obtenerUsuarioInfo($user);
        $invitado = $this->guardarInvitacion($request, $usuarioInfo);
        return response()->json([
            'status' => true,
            'message' => 'Creado con éxito',
            'data' => $invitado,
        ], 201);
    }

    protected function verificarLimiteMensual($documento)
    {
        $inicioMes = Carbon::now()->startOfMonth();
        $finMes = Carbon::now()->endOfMonth();
        return Invitado::where('Documento', $documento)->whereBetween('created_at', [$inicioMes, $finMes])->count();
    }

    protected function obtenerUsuario($userId)
    {
        $user = User::with(['asociado', 'adherente'])->find($userId);
        if (!$user) {
            return response()->json([
                'status' => false,
                'message' => 'No se encontró información del adherente o asociado.'
            ], 404);
        }
        return $user;
    }

    protected function verificarEstadoUsuario($documento)
    {
        $user = User::with(['asociado', 'adherente'])->where('Documento', $documento)->first();
        if ($user) {
            $socio = $user->asociado ?? $user->adherente;
            if ($socio->Estado == 3 || $socio->Estado == 4 || $socio->Estado == 0) {
                return "false";
            }
            return "true";
        }
        return "true";
    }

    protected function obtenerUsuarioInfo($user)
    {
        if ($user->Rol == 2 && $user->asociado) {
            return [
                'Nombre' => $user->asociado->Nombre,
                'Apellidos' => $user->asociado->Apellidos,
                'TipoDocumento' => $user->asociado->TipoDocumento,
                'Documento' => $user->asociado->Documento,
            ];
        }

        if ($user->Rol == 3 && $user->adherente) {
            return [
                'Nombre' => $user->adherente->Nombre,
                'Apellidos' => $user->adherente->Apellidos,
                'TipoDocumento' => $user->adherente->TipoDocumento,
                'Documento' => $user->adherente->Documento,
            ];
        }

        return null;
    }

    protected function guardarInvitacion($request, $usuarioInfo)
    {
        $invitado = Invitado::create([
            'user_id' => $request->user_id,
            'Nombre' => $request->Nombre,
            'Apellidos' => $request->Apellidos,
            'TipoDocumento' => $request->TipoDocumento,
            'Documento' => $request->Documento,
            'Telefono' => $request->Telefono,
            'Status' => $request->Status,
        ]);

        $invitado->usuario_info = $usuarioInfo;

        return $invitado;
    }

    public function getEntradas()
    {
        $entradas = Invitado::with([
            'user.asociado' => function ($query) {
                $query->select('id', 'Nombre', 'Apellidos', 'user_id');
            },
            'user.adherente' => function ($query) {
                $query->select('id', 'Nombre', 'Apellidos', 'user_id');
            }
        ])->where('Status', 1)->orderBy('created_at', 'desc')->get();

        $entradasConNombre = $entradas->map(function ($entrada) {
            $socio = $entrada->user->asociado ?? $entrada->user->adherente;

            return [
                'id' => $entrada->id,
                'Nombre' => $entrada->Nombre,
                'Apellidos' => $entrada->Apellidos,
                'TipoDocumento' => $entrada->TipoDocumento,
                'Documento' => $entrada->Documento,
                'NombreSocio' => $socio->Nombre,
                'ApellidosSocio' => $socio->Apellidos,
                'fecha' => $entrada->created_at
            ];
        });
        return response()->json($entradasConNombre);
    }
}
