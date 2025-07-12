<?php

namespace App\Http\Controllers;

use App\Models\CuotasBaile;
use App\Models\PagosCuotasBaile;
use App\Models\User;
use App\services\CuotasBaileService;
use App\services\UserService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use MercadoPago\Client\Preference\PreferenceClient;
use MercadoPago\MercadoPagoConfig;
use Illuminate\Support\Str;

class CuotasBaileController extends Controller
{

    protected $cuotasService;
    protected $userService;

    public function __construct(CuotasBaileService $cuotasService, UserService $userService)
    {
        $this->cuotasService = $cuotasService;
        $this->userService = $userService;
    }

    public function validatePago($request)
    {
        $rules = [
            'cuotas_baile_id' => 'required|filled',
            'metodo_pago' => 'required|filled',
            'referencia_pago' => 'required|filled',
            'soporte' => 'required|filled',
        ];

        $messages = [
            'cuotas_baile_id.required' => 'La cuota es obligatoria.',
            'metodo_pago.required' => 'El metodo de pago es obligatorio.',
            'referencia_pago.required' => 'La referencia de pago es obligatoria.',
            'soporte.required' => 'El soporte de pago es obligatorio.',
        ];

        $validator = Validator::make($request->all(), $rules, $messages);

        if ($validator->fails()) {
            return [
                'status' => false,
                'errors' => $validator->errors()->all()
            ];
        }

        return [
            'status' => true,
            'message' => 'Validación exitosa'
        ];
    }

    public function getFactura($id)
    {
        return CuotasBaile::find($id);
    }

    public function crearPreferencia(Request $request)
    {
        MercadoPagoConfig::setAccessToken(config('mercadopago.access_token'));

        $factura = $this->getFactura($request->id);
        $valor_cuota = (float) $factura->valor;
        $pagos_actuales = (float) $factura->total_pagos;
        $saldo_factura = $valor_cuota - $pagos_actuales;

        $monto_pagado = $saldo_factura;

        if ($request->filled('valor')) {
            $valorIngresado = (float) $request->valor;

            if ($valorIngresado < $saldo_factura) {
                return response()->json([
                    'status' => false,
                    'errors' => ["El valor ingresado no cubre el saldo pendiente de la cuota principal: $saldo_factura."]
                ], 200);
            }

            $deudas_pendientes = $this->cuotasService
                ->getDeudasPendientes($factura->user_id)
                ->filter(fn($item) => $item->id !== $factura->id);

            if ($pagos_actuales > 0 && $deudas_pendientes->isEmpty() && $valorIngresado > $saldo_factura) {
                return response()->json([
                    'status' => false,
                    'errors' => ["El valor ingresado excede el saldo pendiente de la cuota, y no hay otras cuotas pendientes para aplicar el excedente."]
                ], 200);
            }

            $total_deuda = $this->cuotasService->getDeudasPendientes($factura->user_id)
                ->sum(fn($c) => $c->valor - $c->total_pagos);

            if ($valorIngresado > $total_deuda) {
                return response()->json([
                    'status' => false,
                    'errors' => ["El valor ingresado excede la deuda total del usuario: $total_deuda."]
                ], 200);
            }

            $monto_pagado = $valorIngresado;
        }

        $cliente = new PreferenceClient();

        $preference = $cliente->create([
            "external_reference" => (string) $factura->id,
            "items" => [
                [
                    "title" => "Cuota Baile " . $factura->id,
                    "quantity" => 1,
                    "unit_price" => $monto_pagado,
                    "description" => "Cuota"
                ]
            ],
            "metadata" => [
                "tipo_pago" => "Cuota"
            ],
            "back_urls" => [
                "success" => "https://www.clubsincelejo.prismau.co/pagos-cuotas-baile",
                "failure" => "https://www.clubsincelejo.prismau.co/pagos-cuotas-baile",
                "pending" => "https://www.clubsincelejo.prismau.co/pagos-cuotas-baile"
            ],
            "auto_return" => "approved",
            "notification_url" => "https://apiclubsincelejo.prismau.co/api/webhook"
        ]);

        return response()->json([
            'status' => true,
            'preference_id' => $preference->id,
            'init_point' => $preference->init_point,
        ], 200);
    }

    protected function registrarPago($cuotaId, $monto, Request $request, $url = null)
    {
        PagosCuotasBaile::create([
            "cuotas_baile_id" => $cuotaId,
            "monto" => $monto,
            "referencia_pago" => $request->referencia_pago,
            "fecha_pago" => now(),
            "metodo_pago" => $request->metodo_pago,
            "soporte" => $url,
        ]);
    }

    public function pagarCuota(Request $request)
    {
        $validation = $this->validatePago($request);
        if (!$validation['status']) return $validation;

        $factura = $this->getFactura($request->cuotas_baile_id);

        $monto_pagado = $request->valor ?? $factura->valor;
        $pagos_actuales = (float) $factura->total_pagos;
        $valor_cuota = (float) $factura->valor;
        $saldo_factura = $valor_cuota - $pagos_actuales;

        $deudas_pendientes = $this->cuotasService->getDeudasPendientes($factura->user_id)
            ->filter(fn($item) => $item->id !== $factura->id);

        if ($monto_pagado < $saldo_factura) {
            return response()->json([
                'status' => false,
                'errors' => ["El valor ingresado no cubre el saldo pendiente de la cuota principal: $saldo_factura."]
            ], 200);
        }

        if ($pagos_actuales > 0 && $deudas_pendientes->isEmpty() && $monto_pagado > $saldo_factura) {
            return response()->json([
                'status' => false,
                'errors' => ["El valor ingresado excede el saldo pendiente de la cuota, y no hay otras cuotas pendientes para aplicar el excedente."]
            ], 200);
        }

        $deudas_completas = collect([$factura])->merge($deudas_pendientes);
        $total_deuda_pendiente = $deudas_completas->sum(function ($cuota) {
            return (float) $cuota->valor - (float) $cuota->total_pagos;
        });

        if ($monto_pagado > $total_deuda_pendiente) {
            return response()->json([
                'status' => false,
                'errors' => ["El monto ingresado excede el total de la deuda pendiente del usuario: $total_deuda_pendiente."]
            ], 200);
        }

        $url = null;
        if ($request->hasFile('soporte')) {
            $imagen = $request->file('soporte');
            $nameImage = Str::slug($factura->descripcion) . '_' . time() . '.' . $imagen->getClientOriginalExtension();
            $imagen = $imagen->storeAs('public/soportes', $nameImage);
            $url = Storage::url($imagen);
        }

        if ($request->valor) {
            foreach ($deudas_completas as $cuota) {
                $totalPagos = (float) $cuota->total_pagos;
                $monto_restante = (float) $cuota->valor - $totalPagos;

                if ($monto_pagado >= $monto_restante) {
                    if ($monto_restante > 0) {
                        $this->registrarPago($cuota->id, $monto_restante, $request, $url);
                    }
                    $cuota->update(['estado' => true]);
                    $this->userService->confirmarPagoBailes($cuota->user_id, $cuota->id, "Aprobado");
                    $monto_pagado -= $monto_restante;
                } else {
                    if ($monto_pagado > 0) {
                        $this->registrarPago($cuota->id, $monto_pagado, $request, $url);
                    }
                    $monto_pagado = 0;
                    break;
                }
            }
        } else {
            $factura->update(["estado" => true]);
            $this->registrarPago($factura->id, $factura->valor, $request, $url);
            $this->userService->confirmarPagoBailes($factura->user_id, $factura->id, "Aprobado");
        }

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
