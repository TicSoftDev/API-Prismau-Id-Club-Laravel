<?php

namespace App\services;

use App\Models\Invitado;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

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
        $user = User::with(['asociado', 'adherente', 'familiar'])->find($userId);
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

        if ($user->Rol == 5 && $user->familiar) {
            return [
                'Nombre' => $user->familiar->Nombre,
                'Apellidos' => $user->familiar->Apellidos,
                'TipoDocumento' => $user->familiar->TipoDocumento,
                'Documento' => $user->familiar->Documento,
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
        $entradas = DB::table('invitados as i')
            ->join('users as u', 'u.id', '=', 'i.user_id')
            ->leftJoin('asociados as a', 'a.user_id', '=', 'u.id')
            ->leftJoin('adherentes as ad', 'ad.user_id', '=', 'u.id')
            ->leftJoin('familiars as f', 'f.user_id', '=', 'u.id')
            ->where('i.Status', 1)
            ->orderByDesc('i.created_at')
            ->select([
                'i.id',
                'i.Nombre',
                'i.Apellidos',
                'i.TipoDocumento',
                'i.Documento',
                'i.created_at as fecha',
                DB::raw('COALESCE(a.Nombre, ad.Nombre, f.Nombre) as NombreSocio'),
                DB::raw('COALESCE(a.Apellidos, ad.Apellidos, f.Apellidos) as ApellidosSocio'),
            ])
            ->get();

        return response()->json($entradas);
    }
}
