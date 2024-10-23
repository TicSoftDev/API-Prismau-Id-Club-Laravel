<?php

namespace App\Http\Controllers;

use App\Models\CuotasBaile;
use App\Models\PagosCuotasBaile;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use MercadoPago\Client\Preference\PreferenceClient;
use MercadoPago\MercadoPagoConfig;

class CuotasBaileController extends Controller
{

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
                "success" => "http://localhost:5173/pagos-cuotas-baile",
                "failure" => "http://localhost:5173/pagos-cuotas-baile",
                "pending" => "http://localhost:5173/pagos-cuotas-baile",
            ],
            "auto_return" => "approved",
            "notification_url" => "https://5523-181-78-12-205.ngrok-free.app/api/webhook",
        ]);

        return response()->json($preference->id);
    }

    private function generateUniquePaymentReference()
    {
        do {
            $referencia_pago = rand(10000000, 99999999);
        } while (PagosCuotasBaile::where('referencia_pago', $referencia_pago)->exists());

        return $referencia_pago;
    }

    public function pagarCuota(Request $request)
    {
        $factura = $this->getFactura($request->cuotas_baile_id);
        // $factura->update(['estado' => true]);
        $referencia_pago = $this->generateUniquePaymentReference();
        PagosCuotasBaile::create([
            "cuotas_baile_id" => $factura->id,
            "monto" => $request->valor,
            "referencia_pago" => $referencia_pago,
            "fecha_pago" => now(),
            "metodo_pago" => $request->metodo_pago,
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
                    $cuota->estado = true;
                    $cuota->save();
                }
            }
        }
        return response()->json($user);
    }
}
