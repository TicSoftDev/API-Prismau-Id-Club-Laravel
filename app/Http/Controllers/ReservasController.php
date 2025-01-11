<?php

namespace App\Http\Controllers;

use App\Models\DisponibilidadEspacio;
use App\Models\Reservas;
use App\services\ReservasService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class ReservasController extends Controller
{

    protected $reservasService;

    public function __construct(ReservasService $reservasService)
    {
        $this->reservasService = $reservasService;
    }

    public function crearReservacion(Request $request)
    {
        return $this->reservasService->crearReservacion($request);
    }

    public function reservas()
    {
        return $this->reservasService->reservas();
    }

    public function contReservas()
    {
        return $this->reservasService->contReservas();
    }

    public function getReservasUser($id)
    {
        return $this->reservasService->getReservasUser($id);
    }

    public function contReservasUser($id)
    {
        return $this->reservasService->contReservasUser($id);
    }

    public function cancelarReserva($id)
    {
        return $this->reservasService->cancelarReserva($id);
    }
}
