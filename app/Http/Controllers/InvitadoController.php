<?php

namespace App\Http\Controllers;

use App\Models\Invitado;
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
        if ($cantidadInvitacionesMes >= 4) {
            return response()->json([
                'status' => false,
                'message' => 'Este invitado ya ha sido invitado 4 veces este mes.'
            ], 200);
        } else {
            $invitado =  Invitado::create([
                'user_id' => $request->user_id,
                "Nombre" => $request->Nombre,
                "Apellidos" => $request->Apellidos,
                "TipoDocumento" => $request->TipoDocumento,
                'Documento' => $request->Documento,
                "Telefono" => $request->Telefono,
                'Status' => $request->Status,
            ]);
            return response()->json([
                'status' => true,
                'message' => 'Creado con exito',
                'data' => $invitado
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
