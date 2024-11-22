<?php

namespace App\Http\Controllers;

use App\Models\Mensualidades;
use App\Models\Pagos;
use App\Models\User;
use App\services\MensualidadesService;
use App\services\UserService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use MercadoPago\Client\Preference\PreferenceClient;
use MercadoPago\MercadoPagoConfig;
use Illuminate\Support\Str;

class MensualidadesController extends Controller
{

    protected $mensualidadService;
    protected $userService;

    public function __construct(
        MensualidadesService $mensualidadService,
        UserService $userService
    ) {
        $this->mensualidadService = $mensualidadService;
        $this->userService = $userService;
    }

    public function getFactura($id)
    {
        return Mensualidades::find($id);
    }

    public function crearPreferencia(Request $request)
    {
        MercadoPagoConfig::setAccessToken(config('mercadopago.access_token'));
        $factura = $this->getFactura($request->id);
        $valor = $request->filled('valor') ? $request->valor : $factura->valor;
        $cliente = new PreferenceClient();
        $preference = $cliente->create([
            "external_reference" => (string) $factura->id,
            "items" => [
                [
                    "title" => "Factura " . $factura->id,
                    "quantity" => 1,
                    "unit_price" => (float) $valor,
                    "description" => "Mensualidad",
                ]
            ],
            "back_urls" => [
                "success" => "https://www.clubsincelejo.prismau.co/pagos-mensualidades",
                "failure" => "https://www.clubsincelejo.prismau.co/pagos-mensualidades",
                "pending" => "https://www.clubsincelejo.prismau.co/pagos-mensualidades",
            ],
            "auto_return" => "approved",
            "notification_url" => "https://www.apiclubsincelejo.prismau.co/api/webhook",
        ]);

        return response()->json($preference->id);
    }

    public function pagarMensualidad(Request $request)
    {
        $factura = $this->getFactura($request->mensualidad_id);
        $monto_pagado = $request->valor !== null ? $request->valor : $factura->valor;
        if ($request->hasFile('soporte')) {
            $imagen = $request->file('soporte');
            $nameImage = Str::slug($factura->fecha) . '_' . time() . '.' . $imagen->getClientOriginalExtension();
            $imagen = $imagen->storeAs('public/soportes', $nameImage);
            $url = Storage::url($imagen);
        }

        if ($request->valor !== null) {
            $deudas_pendientes = $this->mensualidadService->getDeudasPendientes($factura->user_id);

            foreach ($deudas_pendientes as $mensualidad) {
                $totalPagos = (float) $mensualidad->total_pagos;
                $monto_restante = (float) $mensualidad->valor - $totalPagos;

                if ($monto_pagado >= $monto_restante) {
                    if ($monto_restante > 0) {
                        Pagos::create([
                            "mensualidad_id" => $mensualidad->id,
                            "monto" => $monto_restante,
                            "referencia_pago" => $request->referencia_pago,
                            "fecha_pago" => now(),
                            "metodo_pago" => $request->metodo_pago,
                            "soporte" => $url,
                        ]);
                    }
                    $mensualidad->update(['estado' => true]);
                    $this->userService->confirmarPago($factura->user_id, $mensualidad->id, "Aprobado");
                    $monto_pagado -= (float) $monto_restante;
                } else {
                    if ($monto_pagado > 0) {
                        Pagos::create([
                            "mensualidad_id" => $mensualidad->id,
                            "monto" => $monto_pagado,
                            "referencia_pago" => $request->referencia_pago,
                            "fecha_pago" => now(),
                            "metodo_pago" => $request->metodo_pago,
                            "soporte" => $url,
                        ]);
                    }
                    $monto_pagado = 0;
                    break;
                }
            }
        } else {
            $factura->update(['estado' => true]);
            Pagos::create([
                "mensualidad_id" => $factura->id,
                "monto" => $factura->valor,
                "referencia_pago" => $request->referencia_pago,
                "fecha_pago" => now(),
                "metodo_pago" => $request->metodo_pago,
                "soporte" => $url,
            ]);
            $this->userService->confirmarPago($factura->user_id, $factura->id, "Aprobado");
        }
        // $deuda = $this->mensualidadService->getCantidadDeudas($factura->user_id);
        // if ($deuda < 3) {
        //     $this->userService->cambiarEstado($factura->user_id, 1);
        // } else if ($deuda < 6) {
        //     $this->userService->cambiarEstado($factura->user_id, 0);
        // } else if ($deuda < 11) {
        //     $this->userService->cambiarEstado($factura->user_id, 3);
        // } else {
        //     $this->userService->cambiarEstado($factura->user_id, 4);
        // }

        return response()->json([
            'status' => true,
            'message' => 'Pago exitoso'
        ], 200);
    }

    public function consultarMensualidadesUser($documento)
    {
        $user = User::with([
            'mensualidades.pago',
            'asociado',
            'adherente'
        ])->where('Documento', $documento)->first();
        if ($user) {
            foreach ($user->mensualidades as $mensualidad) {
                if ($mensualidad->total_pagos >= $mensualidad->valor) {
                    $mensualidad->estado = 1;
                    $mensualidad->save();
                }
            }
        }
        return response()->json($user);
    }

    public function cambiarValorMensualidadUser(Request $request)
    {
        return $this->mensualidadService->actualizarValorMensualidadesSocio($request->documento, $request->valor);
    }
}
