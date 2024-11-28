<?php

namespace App\Http\Controllers;

use App\Models\Invitado;
use App\Models\User;
use App\services\InvitadosService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class InvitadoController extends Controller
{

    protected $invitadosService;

    public function __construct(InvitadosService $invitadosService)
    {
        $this->invitadosService = $invitadosService;
    }

    public function crearInvitacion(Request $request)
    {
        return $this->invitadosService->crearInvitacion($request);
    }

    public function invitados()
    {
        $invitados = Invitado::with(['user.asociado', 'user.adherente'])->get();
        return response()->json($invitados);
    }

    public function contInvitadosMes()
    {
        $inicioMes = Carbon::now()->startOfMonth();
        $finMes = Carbon::now()->endOfMonth();
        $cantidadInvitadosMes = Invitado::whereBetween('created_at', [$inicioMes, $finMes])->count();
        return response()->json($cantidadInvitadosMes);
    }

    public function contInvitadosUser(String $id)
    {
        $inicioMes = Carbon::now()->startOfMonth();
        $finMes = Carbon::now()->endOfMonth();
        $contador = Invitado::where('user_id', $id)
            ->whereBetween('created_at', [$inicioMes, $finMes])
            ->count();
        return response()->json($contador);
    }

    public function entradasInvitados()
    {
        return $this->invitadosService->getEntradas();
    }

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
}
