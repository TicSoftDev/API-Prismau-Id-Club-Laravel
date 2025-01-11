<?php

namespace App\Http\Controllers;

use App\Models\Contratos;
use App\services\ContratosService;
use Illuminate\Http\Request;

class ContratosController extends Controller
{

    protected $contratosService;

    public function __construct(ContratosService $contratosService)
    {
        $this->contratosService = $contratosService;
    }

    public function crearSolicitudContratoApp(Request $request)
    {
        return $this->contratosService->crearSolicitudContratoApp($request);
    }

    public function contratosApp()
    {
        return $this->contratosService->contratosApp();
    }

    public function contContratosApp()
    {
        return $this->contratosService->contContratosApp();
    }
}
