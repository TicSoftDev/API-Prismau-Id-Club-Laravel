<?php

namespace App\Http\Controllers;

use App\Models\DisponibilidadEspacio;
use App\Models\Reservas;
use Carbon\Carbon;
use Illuminate\Http\Request;

class ReservasController extends Controller
{

    public function crearReservacion(Request $request)
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'espacio_id' => 'required|exists:espacios,id',
            'Fecha' => 'required|date',
            'Inicio' => 'required|date_format:H:i',
            'Fin' => 'required|date_format:H:i|after:hora_inicio',
        ]);

        $espacioId = $validated['espacio_id'];
        $fecha = $validated['Fecha'];
        $horaInicio = $validated['Inicio'];
        $horaFin = $validated['Fin'];
        $diaSemana = Carbon::parse($fecha)->format('l');

        $diasSemana = [
            'Monday' => 'Lunes',
            'Tuesday' => 'Martes',
            'Wednesday' => 'Miércoles',
            'Thursday' => 'Jueves',
            'Friday' => 'Viernes',
            'Saturday' => 'Sábado',
            'Sunday' => 'Domingo'
        ];
        $diaSemanaEsp = $diasSemana[$diaSemana];

        $disponibilidad = DisponibilidadEspacio::where('espacio_id', $espacioId)
            ->where('Dia', $diaSemanaEsp)
            ->where('Inicio', '<=', $horaInicio)
            ->where('Fin', '>=', $horaFin)
            ->exists();

        if (!$disponibilidad) {
            return response()->json([
                'status' => false,
                'error' => 'No Disponible'
            ], 422);
        }

        $conflictos = Reservas::where('espacio_id', $espacioId)
            ->where('Fecha', $fecha)
            ->where(function ($query) use ($horaInicio, $horaFin) {
                $query->whereBetween('Inicio', [$horaInicio, $horaFin])
                    ->orWhereBetween('Fin', [$horaInicio, $horaFin])
                    ->orWhere(function ($query) use ($horaInicio, $horaFin) {
                        $query->where('Inicio', '<', $horaInicio)
                            ->where('Fin', '>', $horaFin);
                    });
            })
            ->exists();

        if ($conflictos) {
            return response()->json([
                'status' => false,
                'error' => 'Reservado'
            ], 422);
        }

        $reserva = Reservas::create($validated);

        return response()->json([
            'status' => true,
            'data' => $reserva
        ], 201);
    }

    public function getReservasUser($id)
    {
        $reservas = Reservas::with('espacio')
            ->where('user_id', $id)
            ->orderBy('fecha', 'desc')
            ->get();
        return response()->json($reservas);
    }

    public function cancelarReserva($id)
    {
        $reservas = Reservas::findOrFail($id);
        $reservas->delete();
        return response()->json([
            'status' => true,
            'message' => 'Reserva Cancelada',
        ]);
    }
}
