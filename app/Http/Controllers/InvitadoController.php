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
    public function index()
    {
        $invitados = Invitado::with('personal')->get();
        return response()->json($invitados);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $inicioMes = Carbon::now()->startOfMonth();
        $finMes = Carbon::now()->endOfMonth();
        $cantidadInvitadosMes = Invitado::whereBetween('created_at', [$inicioMes, $finMes])->count();
        return response()->json($cantidadInvitadosMes);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
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
            Invitado::create($request->all());
            return response()->json([
                'status' => true,
                'message' => 'Creado con exito'
            ], 201);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Invitado $invitado)
    {
        //
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
    public function update(Request $request, Invitado $invitado)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Invitado $invitado)
    {
        //
    }
}
