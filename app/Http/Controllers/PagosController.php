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

        if (($data['type'] ?? '') === 'payment') {
            $paymentId = $data['data']['id'] ?? null;

            if (!$paymentId) {
                return response()->json(['status' => false, 'message' => 'ID de pago faltante'], 400);
            }

            $respuesta = Http::get("https://api.mercadopago.com/v1/payments/$paymentId?access_token=" . $this->accessToken)->json();

            $tipo = $respuesta['metadata']['tipo_pago'] ?? null;
            if (!$tipo && isset($respuesta['additional_info']['items'][0]['description'])) {
                $tipo = $respuesta['additional_info']['items'][0]['description'];
            }

            if ($tipo) {
                if (str_contains($tipo, 'Mensualidad')) {
                    return $this->webhookMensualidades($paymentId);
                } elseif (str_contains($tipo, 'Cuota')) {
                    return $this->webhookCuotasBaile($paymentId);
                }
            }

            return response()->json(['status' => false, 'message' => 'Tipo de pago no reconocido'], 400);
        }

        return response()->json(['status' => false, 'message' => 'Evento no válido'], 400);
    }

    public function webhookMensualidades($paymentId)
    {
        $respuesta = Http::get("https://api.mercadopago.com/v1/payments/$paymentId?access_token=" . $this->accessToken)->json();

        if (!$respuesta || ($respuesta['status'] ?? '') !== 'approved') {
            return response()->json(['status' => false], 200);
        }

        $external_reference = $respuesta['external_reference'] ?? null;
        if (!$external_reference) {
            return response()->json(['status' => false], 400);
        }

        $factura = Mensualidades::find($external_reference);
        if (!$factura) {
            return response()->json(['status' => false], 404);
        }

        $existe = Pagos::where('referencia_pago', $paymentId)->exists();
        if ($existe) {
            return response()->json(['status' => true, 'message' => 'Pago ya procesado']);
        }

        try {
            $monto_pagado = $respuesta['transaction_amount'];
            $fechaPago = Carbon::parse($respuesta['date_created'])->format('Y-m-d H:i:s');
            $deudas = $this->mensualidadService->getDeudasPendientes($factura->user_id);

            foreach ($deudas as $mensualidad) {
                $monto_restante = $mensualidad->valor - $mensualidad->total_pagos;

                if ($monto_pagado >= $monto_restante && $monto_restante > 0) {
                    Pagos::create([
                        'mensualidad_id' => $mensualidad->id,
                        'email' => $respuesta['payer']['email'] ?? null,
                        'nombre' => ($respuesta['payer']['first_name'] ?? '') . ' ' . ($respuesta['payer']['last_name'] ?? ''),
                        'identificacion' => $respuesta['payer']['identification']['number'] ?? null,
                        'metodo_pago' => $respuesta['payment_method']['type'] ?? null,
                        'referencia_pago' => $paymentId,
                        'monto' => $monto_restante,
                        'tarjeta' => $respuesta['card']['last_four_digits'] ?? null,
                        'fecha_pago' => $fechaPago,
                    ]);
                    $mensualidad->update(['estado' => true]);
                    $this->userService->confirmarPago($factura->user_id, $mensualidad->id, "Aprobado");
                    $monto_pagado -= $monto_restante;
                } elseif ($monto_pagado > 0) {
                    Pagos::create([
                        'mensualidad_id' => $mensualidad->id,
                        'email' => $respuesta['payer']['email'] ?? null,
                        'nombre' => ($respuesta['payer']['first_name'] ?? '') . ' ' . ($respuesta['payer']['last_name'] ?? ''),
                        'identificacion' => $respuesta['payer']['identification']['number'] ?? null,
                        'metodo_pago' => $respuesta['payment_method']['type'] ?? null,
                        'referencia_pago' => $paymentId,
                        'monto' => $monto_pagado,
                        'tarjeta' => $respuesta['card']['last_four_digits'] ?? null,
                        'fecha_pago' => $fechaPago,
                    ]);
                    break;
                }
            }
            return response()->json(['status' => true, 'message' => 'Pago procesado']);
        } catch (\Exception $e) {
            Log::error('Error al procesar el pago', ['error' => $e->getMessage()]);
            return response()->json(['status' => false, 'error' => $e->getMessage()], 500);
        }
    }

    public function webhookCuotasBaile($paymentId)
    {
        $respuesta = Http::get("https://api.mercadopago.com/v1/payments/$paymentId?access_token=" . $this->accessToken)->json();

        if (!$respuesta || ($respuesta['status'] ?? '') !== 'approved') {
            return response()->json(['status' => false], 200);
        }

        $external_reference = $respuesta['external_reference'] ?? null;
        if (!$external_reference) {
            return response()->json(['status' => false], 400);
        }

        $factura = CuotasBaile::find($external_reference);
        if (!$factura) {
            return response()->json(['status' => false], 404);
        }

        $existe = PagosCuotasBaile::where('referencia_pago', $paymentId)->exists();
        if ($existe) {
            return response()->json(['status' => true, 'message' => 'Pago ya procesado']);
        }

        try {
            $fechaPago = Carbon::parse($respuesta['date_created'])->format('Y-m-d H:i:s');

            PagosCuotasBaile::create([
                'cuotas_baile_id' => $factura->id,
                'email' => $respuesta['payer']['email'] ?? null,
                'nombre' => ($respuesta['payer']['first_name'] ?? '') . ' ' . ($respuesta['payer']['last_name'] ?? ''),
                'identificacion' => $respuesta['payer']['identification']['number'] ?? null,
                'metodo_pago' => $respuesta['payment_method']['type'] ?? null,
                'referencia_pago' => $paymentId,
                'monto' => $respuesta['transaction_amount'] ?? 0,
                'tarjeta' => $respuesta['card']['last_four_digits'] ?? null,
                'fecha_pago' => $fechaPago,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $factura->update(['estado' => true]);

            return response()->json(['status' => true, 'message' => 'Pago de cuota registrado']);
        } catch (\Exception $e) {
            Log::error('Error al registrar pago de cuota de baile', ['error' => $e->getMessage()]);
            return response()->json(['status' => false, 'error' => $e->getMessage()], 500);
        }
    }
}
