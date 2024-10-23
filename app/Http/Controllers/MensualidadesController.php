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
use MercadoPago\Client\Preference\PreferenceClient;
use MercadoPago\MercadoPagoConfig;

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
        $cliente = new PreferenceClient();
        $preference = $cliente->create([
            "external_reference" => (string) $factura->id,
            "items" => [
                [
                    "title" => "Factura " . $factura->id,
                    "quantity" => 1,
                    "unit_price" => (float) $factura->valor,
                    "description" => "Mensualidad",
                ]
            ],
            "back_urls" => [
                "success" => "http://localhost:5173/pagos-mensualidades",
                "failure" => "http://localhost:5173/pagos-mensualidades",
                "pending" => "http://localhost:5173/pagos-mensualidades",
            ],
            "auto_return" => "approved",
            "notification_url" => "https://5523-181-78-12-205.ngrok-free.app/api/webhook",
        ]);

        return response()->json($preference->id);
    }

    public function pagarMensualidad(Request $request)
    {
        $factura = $this->getFactura($request->mensualidad_id);
        $factura->update(['estado' => true]);
        $deuda = $this->mensualidadService->getCantidadDeudas($factura->user_id);
        if ($deuda < 3) {
            $this->userService->cambiarEstado($factura->user_id, 1);
        } else if ($deuda < 6) {
            $this->userService->cambiarEstado($factura->user_id, 0);
        } else if ($deuda < 11) {
            $this->userService->cambiarEstado($factura->user_id, 3);
        } else {
            $this->userService->cambiarEstado($factura->user_id, 4);
        }
        $referencia_pago = $this->generateUniquePaymentReference();
        Pagos::create([
            "mensualidad_id" => $factura->id,
            "monto" => $factura->valor,
            "referencia_pago" => $referencia_pago,
            "fecha_pago" => now(),
            "metodo_pago" => $request->metodo_pago,
        ]);
        $this->userService->confirmarPago($factura->user_id, $factura->id, "Aprobado");
        return response()->json([
            'status' => true,
            'message' => 'Pago exitoso'
        ], 200);
    }

    private function generateUniquePaymentReference()
    {
        do {
            $referencia_pago = rand(10000000, 99999999);
        } while (Pagos::where('referencia_pago', $referencia_pago)->exists());

        return $referencia_pago;
    }

    public function consultarMensualidadesUser($documento)
    {
        $user = User::with([
            'mensualidades.pago',
            'asociado',
            'adherente'
        ])->where('Documento', $documento)->first();
        return response()->json($user);
    }
}
