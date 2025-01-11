<?php

namespace App\Http\Controllers;

use App\Models\Solicitudes;
use App\services\SolicitudesService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class SolicitudesController extends Controller
{

    protected $solicitudService;

    public function __construct(SolicitudesService $solicitudService)
    {
        $this->solicitudService = $solicitudService;
    }

    public function crearSolicitud(Request $request)
    {
        return $this->solicitudService->crearSolicitud($request);
    }

    public function solicitudes()
    {
        return $this->solicitudService->solicitudes();
    }

    public function solicitud($id)
    {
        return $this->solicitudService->solicitud($id);
    }

    public function getSolicitudUser($id)
    {
        return $this->solicitudService->getSolicitudUser($id);
    }

    public function contSolicitudesPendientes()
    {
        return $this->solicitudService->contSolicitudesPendientes();
    }

    public function contSolicitudesUser($id)
    {
        return $this->solicitudService->contSolicitudesUser($id);
    }

    public function responderSolicitud(Request $request, $id)
    {
        return $this->solicitudService->responderSolicitud($request, $id);
    }
}
