<?php

namespace App\Http\Controllers;

use App\Models\CuotasBaile;
use App\Models\PagosCuotasBaile;
use App\Models\User;
use App\services\CuotasBaileService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use MercadoPago\Client\Preference\PreferenceClient;
use MercadoPago\MercadoPagoConfig;
use Illuminate\Support\Str;

class CuotasBaileController extends Controller
{

    public $cuotasService;

    public function __construct(CuotasBaileService $cuotasService)
    {
        $this->cuotasService = $cuotasService;
    }

    public function getFactura($id)
    {
        return CuotasBaile::find($id);
    }

    public function crearPreferencia(Request $request)
    {
        MercadoPagoConfig::setAccessToken(config('mercadopago.access_token'));
        $factura = $this->getFactura($request->id);
        $cliente = new PreferenceClient();
        $preference = $cliente->create([
            "external_reference" => (string) $factura->id,
            "items" => [
                [
                    "title" => "Cuota Baile " . $factura->id,
                    "quantity" => 1,
                    "unit_price" => (float) $request->valor,
                    "description" => "Cuota",
                ]
            ],
            "back_urls" => [
                "success" => "https://www.clubsincelejo.prismau.co/pagos-cuotas-baile",
                "failure" => "https://www.clubsincelejo.prismau.co/pagos-cuotas-baile",
                "pending" => "https://www.clubsincelejo.prismau.co/pagos-cuotas-baile",
            ],
            "auto_return" => "approved",
            "notification_url" => "https://www.apiclubsincelejo.prismau.co/api/webhook",
        ]);

        return response()->json($preference->id);
    }

    public function pagarCuota(Request $request)
    {
        $factura = $this->getFactura($request->cuotas_baile_id);
        if ($request->hasFile('soporte')) {
            $imagen = $request->file('soporte');
            $nameImage = Str::slug($factura->año) . '_' . time() . '.' . $imagen->getClientOriginalExtension();
            $imagen = $imagen->storeAs('public/soportes', $nameImage);
            $url = Storage::url($imagen);
        }
        PagosCuotasBaile::create([
            "cuotas_baile_id" => $factura->id,
            "monto" => $request->valor,
            "referencia_pago" => $request->referencia_pago,
            "fecha_pago" => now(),
            "metodo_pago" => $request->metodo_pago,
            "soporte" => $url
        ]);
        return response()->json([
            'status' => true,
            'message' => 'Pago exitoso'
        ], 200);
    }

    public function consultarCuotasBaileUser($documento)
    {
        $user = User::with([
            'cuotas.pago',
            'asociado',
            'adherente'
        ])->where('Documento', $documento)->first();

        if ($user) {
            foreach ($user->cuotas as $cuota) {
                if ($cuota->total_pagos >= $cuota->valor) {
                    $cuota->estado = 1;
                    $cuota->save();
                }
            }
        }
        return response()->json($user);
    }

    public function cambiarValorCuotasBaileUser(Request $request)
    {
        return $this->cuotasService->actualizarValorCuotasBaileSocio($request->documento, $request->valor);
    }
}
