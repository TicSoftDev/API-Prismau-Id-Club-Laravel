<?php

namespace App\Http\Controllers;

use App\Models\CuotasBaile;
use App\Models\Mensualidades;
use App\Models\Pagos;
use App\Models\PagosCuotasBaile;
use App\Models\Rubros;
use App\Models\User;
use App\services\MensualidadesService;
use App\services\UserService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PagosController extends Controller
{

    protected $accessToken;
    protected $mensualidadService;
    protected $userService;

    public function __construct(
        MensualidadesService $mensualidadService,
        UserService $userService
    ) {
        $this->accessToken = config('mercadopago.access_token');
        $this->mensualidadService = $mensualidadService;
        $this->userService = $userService;
    }

    public function generarFacturas(Request $request)
    {
        $rubro = Rubros::findOrFail($request->rubro_id);
        $valor = $rubro->valor;
        $usuarios = User::whereIn('Rol', [2, 3])->get();

        $mensualidadesExistentes = strcasecmp($rubro->rubro, 'mensualidad') === 0
            ? Mensualidades::whereIn('user_id', $usuarios->pluck('id'))->whereYear('fecha', $request->año)
            ->where('valor', $valor)->get()->keyBy('user_id')
            : collect();

        $cuotasExistentes = strcasecmp($rubro->rubro, 'Couta de baile') === 0
            ? CuotasBaile::whereIn('user_id', $usuarios->pluck('id'))
            ->where('año', $request->año)->where('valor', $valor)->get()->keyBy('user_id')
            : collect();

        if (strcasecmp($rubro->rubro, 'mensualidad') === 0 && $mensualidadesExistentes->count() === $usuarios->count()) {
            return response()->json([
                'status' => false,
                'message' => "Todos los usuarios ya tienen mensualidades para el año {$request->año}.",
            ], 200);
        }

        if (strcasecmp($rubro->rubro, 'Couta de baile') === 0 && $cuotasExistentes->count() === $usuarios->count()) {
            return response()->json([
                'status' => false,
                'message' => "Todos los usuarios ya tienen cuotas de baile para el año {$request->año}.",
            ], 200);
        }

        foreach ($usuarios as $usuario) {
            if (strcasecmp($rubro->rubro, 'mensualidad') === 0 && $mensualidadesExistentes->has($usuario->id)) {
                continue;
            }
            if (strcasecmp($rubro->rubro, 'Couta de baile') === 0 && $cuotasExistentes->has($usuario->id)) {
                continue;
            }
            if (strcasecmp($rubro->rubro, 'mensualidad') === 0) {
                for ($mes = 1; $mes <= 12; $mes++) {
                    $fechaFactura = Carbon::create($request->año, $mes, 1);
                    Mensualidades::create([
                        'user_id' => $usuario->id,
                        'fecha' => $fechaFactura,
                        'valor' => $valor,
                        'estado' => false,
                    ]);
                }
            } else {
                CuotasBaile::create([
                    'user_id' => $usuario->id,
                    'año' => $request->año,
                    'valor' => $valor,
                    'estado' => false,
                ]);
            }
        }

        return response()->json([
            'status' => true,
            'message' => "Facturas generadas correctamente"
        ]);
    }

    public function getPagos()
    {
        return Pagos::with([
            'mensualidad',
            'mensualidad.user',
            'mensualidad.user.asociado',
            'mensualidad.user.adherente'
        ])->get();
    }

    public function handleWebhook(Request $request)
    {
        $data = $request->all();
        if (isset($data['type']) && $data['type'] == 'payment') {
            $paymentId = $data['data']['id'];
            $respuesta =  Http::get("https://api.mercadopago.com/v1/payments/$paymentId?access_token=" . $this->accessToken)->json();
        }
        if (isset($respuesta['additional_info']['items'][0]['description'])) {
            $paymentType = $respuesta['additional_info']['items'][0]['description'];

            switch (true) {
                case strpos($paymentType, 'Mensualidad') !== false:
                    return $this->webhookMensualidades($request);
                case strpos($paymentType, 'Cuota') !== false:
                    return $this->webhookCuotasBaile($request);
                default:
                    return response()->json(['status' => false, 'message' => 'Tipo de pago no reconocido'], 400);
            }
        } else {
            return response()->json(['status' => false, 'message' => 'Descripción de pago no disponible'], 400);
        }
        return response()->json(['status' => false, 'message' => 'Tipo de evento no reconocido'], 400);
    }

    public function webhookMensualidades(Request $request)
    {
        $data = $request->all();
        if (isset($data['type']) && $data['type'] == 'payment') {
            $paymentId = $data['data']['id'];
            $respuesta =  Http::get("https://api.mercadopago.com/v1/payments/$paymentId?access_token=" . $this->accessToken)->json();
            if ($respuesta) {
                $external_reference = $respuesta['external_reference'];
                if ($data['action'] == 'payment.created') {
                    $pago = Pagos::where('referencia_pago', $paymentId)->first();
                    if (!$pago && $respuesta['status'] == 'approved') {
                        $factura = Mensualidades::where('id', $external_reference)->first();
                        if ($factura) {
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
                            try {
                                Pagos::create([
                                    'mensualidad_id' => $external_reference,
                                    'email' => $respuesta['payer']['email'] ?? null,
                                    'nombre' => ($respuesta['payer']['first_name'] ?? '') . ' ' . ($respuesta['payer']['last_name'] ?? ''),
                                    'identificacion' => $respuesta['payer']['identification']['number'] ?? null,
                                    'metodo_pago' => $respuesta['payment_method']['type'] ?? null,
                                    'referencia_pago' => $paymentId,
                                    'monto' => $respuesta['transaction_amount'] ?? 0,
                                    'tarjeta' => $respuesta['card']['last_four_digits'] ?? null,
                                    'fecha_pago' => $respuesta['date_created'] ?? null,
                                ]);
                                $this->userService->confirmarPago($factura->user_id, $external_reference, $respuesta['status']);
                            } catch (\Exception $e) {
                                return response()->json([
                                    'status' => false,
                                    'error' => 'Error creando el pago: ' . $e->getMessage()
                                ], 500);
                            }
                        }
                    }
                }
            } else {
                return response()->json([
                    'status' => false,
                    'error' => 'Error retrieving payment details from Mercado Pago'
                ], 200);
            }
        }
        return response()->json([
            'status' => true,
            'message' => 'Pago exitoso'
        ], 200);
    }

    public function webhookCuotasBaile(Request $request)
    {
        $data = $request->all();
        if (isset($data['type']) && $data['type'] == 'payment') {
            $paymentId = $data['data']['id'];
            $respuesta =  Http::get("https://api.mercadopago.com/v1/payments/$paymentId?access_token=" . env('ACCESS_TOKEN'))->json();
            if ($respuesta) {
                $external_reference = $respuesta['external_reference'];
                if ($data['action'] == 'payment.created') {
                    $pago = PagosCuotasBaile::where('referencia_pago', $paymentId)->first();
                    if (!$pago && $respuesta['status'] == 'approved') {
                        $factura = CuotasBaile::where('id', $external_reference)->first();
                        if ($factura) {
                            // $factura->update(['estado' => true]);
                            try {
                                PagosCuotasBaile::create([
                                    'cuotas_baile_id' => $external_reference,
                                    'email' => $respuesta['payer']['email'] ?? null,
                                    'nombre' => ($respuesta['payer']['first_name'] ?? '') . ' ' . ($respuesta['payer']['last_name'] ?? ''),
                                    'identificacion' => $respuesta['payer']['identification']['number'] ?? null,
                                    'metodo_pago' => $respuesta['payment_method']['type'] ?? null,
                                    'referencia_pago' => $paymentId,
                                    'monto' => $respuesta['transaction_amount'] ?? 0,
                                    'tarjeta' => $respuesta['card']['last_four_digits'] ?? null,
                                    'fecha_pago' => $respuesta['date_created'] ?? null,
                                ]);
                            } catch (\Exception $e) {
                                return response()->json([
                                    'status' => false,
                                    'error' => 'Error creando el pago: ' . $e->getMessage()
                                ], 500);
                            }
                        }
                    }
                }
            } else {
                return response()->json([
                    'status' => false,
                    'error' => 'Error retrieving payment details from Mercado Pago'
                ], 200);
            }
        }
        return response()->json([
            'status' => true,
            'message' => 'Pago exitoso'
        ], 200);
    }
}
