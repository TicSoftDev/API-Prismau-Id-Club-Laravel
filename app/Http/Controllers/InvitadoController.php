<?php

namespace App\Http\Controllers;

use App\Models\Invitado;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;

class InvitadoController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function invitados()
    {
        $invitados = Invitado::with(['user.asociado', 'user.adherente'])->get();
        return response()->json($invitados);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function contInvitadosMes()
    {
        $inicioMes = Carbon::now()->startOfMonth();
        $finMes = Carbon::now()->endOfMonth();
        $cantidadInvitadosMes = Invitado::whereBetween('created_at', [$inicioMes, $finMes])->count();
        return response()->json($cantidadInvitadosMes);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function crearInvitacion(Request $request)
    {
        $inicioMes = Carbon::now()->startOfMonth();
        $finMes = Carbon::now()->endOfMonth();

        $cantidadInvitacionesMes = Invitado::where('Documento', $request->Documento)
            ->whereBetween('created_at', [$inicioMes, $finMes])
            ->count();

        if ($cantidadInvitacionesMes >= 2) {
            return response()->json([
                'status' => false,
                'message' => 'Este invitado ya ha sido invitado 2 veces este mes.'
            ], 200);
        } else {
            $user = User::with(['asociado', 'adherente'])->find($request->user_id);

            if (!$user) {
                return response()->json([
                    'status' => false,
                    'message' => 'Usuario no encontrado.'
                ], 404);
            }

            $usuarioInfo = null;
            if ($user->Rol == 2) {
                $usuarioInfo = $user->asociado ? [
                    'Nombre' => $user->asociado->Nombre,
                    'Apellidos' => $user->asociado->Apellidos,
                    'TipoDocumento' => $user->asociado->TipoDocumento,
                    'Documento' => $user->asociado->Documento,
                ] : null;
            } elseif ($user->Rol == 3) {
                $usuarioInfo = $user->adherente ? [
                    'Nombre' => $user->adherente->Nombre,
                    'Apellidos' => $user->adherente->Apellidos,
                    'TipoDocumento' => $user->adherente->TipoDocumento,
                    'Documento' => $user->adherente->Documento,
                ] : null;
            }

            if (!$usuarioInfo) {
                return response()->json([
                    'status' => false,
                    'message' => 'No se encontró información del adherente o asociado.'
                ], 404);
            }

            $invitado = Invitado::create([
                'user_id' => $request->user_id,
                "Nombre" => $request->Nombre,
                "Apellidos" => $request->Apellidos,
                "TipoDocumento" => $request->TipoDocumento,
                'Documento' => $request->Documento,
                "Telefono" => $request->Telefono,
                'Status' => $request->Status,
            ]);

            $invitado->usuario_info = $usuarioInfo;

            return response()->json([
                'status' => true,
                'message' => 'Creado con éxito',
                'data' => $invitado, 
            ], 201);
        }
    }


    /**
     * Display the specified resource.
     */
    public function contInvitadosUser(String $id)
    {
        $inicioMes = Carbon::now()->startOfMonth();
        $finMes = Carbon::now()->endOfMonth();
        $contador = Invitado::where('user_id', $id)
            ->whereBetween('created_at', [$inicioMes, $finMes])
            ->count();
        return response()->json($contador);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Invitado $invitado)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(String $id)
    {
        $invitado = Invitado::find($id);
        $invitado->update([
            'Status' => true,
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Actualizado con exito',
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Invitado $invitado)
    {
        //
    }
}
