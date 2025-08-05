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
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
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

    public function validatePago($request)
    {
        $rules = [
            'mensualidad_id' => 'required|filled',
            'metodo_pago' => 'required|filled',
            'referencia_pago' => 'required|filled',
            'soporte' => 'required|mimes:jpg,png,pdf|max:5120|filled',
        ];

        $messages = [
            'mensualidad_id.required' => 'La cuota es obligatoria.',
            'metodo_pago.required' => 'El metodo de pago es obligatorio.',
            'referencia_pago.required' => 'La referencia de pago es obligatoria.',
            'soporte.required' => 'El soporte de pago es obligatorio.',
            'soporte.max' => 'El soporte de pago no debe superar los 5MB.',
            'soporte.mimes' => 'El soporte de pago debe ser un archivo de tipo jpg, png o pdf.',
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

    protected function registrarPagoMensualidad($mensualidadId, $monto, Request $request, $url = null)
    {
        Pagos::create([
            "mensualidad_id" => $mensualidadId,
            "monto" => $monto,
            "referencia_pago" => $request->referencia_pago,
            "fecha_pago" => now(),
            "metodo_pago" => $request->metodo_pago,
            "soporte" => $url,
        ]);
    }

    public function crearPreferencia(Request $request)
    {
        MercadoPagoConfig::setAccessToken(config('mercadopago.access_token'));

        $factura = $this->getFactura($request->id);
        $valor_factura = (float) $factura->valor;
        $pagos_actuales = (float) $factura->total_pagos;
        $saldo_factura = $valor_factura - $pagos_actuales;

        $valor = $saldo_factura;

        if ($request->filled('valor')) {
            $valorIngresado = (float) $request->valor;

            if ($valorIngresado < $saldo_factura) {
                return response()->json([
                    'status' => false,
                    'errors' => ["El valor ingresado no cubre el saldo pendiente de la mensualidad principal: $saldo_factura."]
                ], 200);
            }

            $deudas = $this->mensualidadService->getDeudasPendientes($factura->user_id)
                ->filter(fn($item) => $item->id !== $factura->id);

            if ($pagos_actuales > 0 && $deudas->isEmpty() && $valorIngresado > $saldo_factura) {
                return response()->json([
                    'status' => false,
                    'errors' => ["El valor ingresado excede el saldo pendiente de la mensualidad, y no hay otras mensualidades pendientes para aplicar el excedente."]
                ], 200);
            }

            $total_deuda = $this->mensualidadService->getDeudasPendientes($factura->user_id)
                ->sum(fn($m) => $m->valor - $m->total_pagos);

            if ($valorIngresado > $total_deuda) {
                return response()->json([
                    'status' => false,
                    'errors' => ["El valor ingresado excede la deuda total del usuario: $total_deuda."]
                ], 200);
            }

            $valor = $valorIngresado;
        }

        $cliente = new PreferenceClient();

        $preference = $cliente->create([
            "external_reference" => (string) $factura->id,
            "items" => [
                [
                    "title" => "Factura " . $factura->id,
                    "quantity" => 1,
                    "unit_price" => $valor,
                    "description" => "Mensualidad",
                ]
            ],
            "metadata" => [
                "tipo_pago" => "Mensualidad"
            ],
            "back_urls" => [
                "success" => "https://www.clubsincelejo.prismau.co/pagos-mensualidades",
                "failure" => "https://www.clubsincelejo.prismau.co/pagos-mensualidades",
                "pending" => "https://www.clubsincelejo.prismau.co/pagos-mensualidades",
            ],
            "auto_return" => "approved",
            "notification_url" => "https://apiclubsincelejo.prismau.co/api/webhook",
        ]);

        return response()->json([
            'status' => true,
            'preference_id' => $preference->id,
            'init_point' => $preference->init_point,
        ], 200);
    }

    public function pagarMensualidad(Request $request)
    {
        $validation = $this->validatePago($request);
        if (!$validation['status']) return $validation;

        $factura = $this->getFactura($request->mensualidad_id);

        $monto_pagado = $request->valor ?? $factura->valor;
        $pagos_actuales = (float) $factura->total_pagos;
        $valor_cuota = (float) $factura->valor;
        $saldo_factura = $valor_cuota - $pagos_actuales;

        $deudas_pendientes = $this->mensualidadService->getDeudasPendientes($factura->user_id)
            ->filter(fn($item) => $item->id !== $factura->id);

        if ($monto_pagado < $saldo_factura) {
            return response()->json([
                'status' => false,
                'errors' => ["El valor ingresado no cubre el saldo pendiente de la mensualidad principal: $saldo_factura."]
            ], 200);
        }

        $deudas_completas = collect([$factura])->merge($deudas_pendientes);

        $total_deuda_pendiente = $deudas_completas->sum(function ($mensualidad) {
            return (float) $mensualidad->valor - (float) $mensualidad->total_pagos;
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
            $nameImage = Str::slug($factura->fecha) . '_' . time() . '.' . $imagen->getClientOriginalExtension();
            $imagen = $imagen->storeAs('public/soportes', $nameImage);
            $url = Storage::url($imagen);
        }

        if ($request->valor !== null) {
            foreach ($deudas_completas as $mensualidad) {
                $totalPagos = (float) $mensualidad->total_pagos;
                $monto_restante = (float) $mensualidad->valor - $totalPagos;

                if ($monto_pagado >= $monto_restante) {
                    if ($monto_restante > 0) {
                        $this->registrarPagoMensualidad($mensualidad->id, $monto_restante, $request, $url);
                    }
                    $mensualidad->update(['estado' => true]);
                    $this->userService->confirmarPago($factura->user_id, $mensualidad->id, "Aprobado");
                    $monto_pagado -= $monto_restante;
                } else {
                    if ($monto_pagado > 0) {
                        $this->registrarPagoMensualidad($mensualidad->id, $monto_pagado, $request, $url);
                    }
                    $monto_pagado = 0;
                    break;
                }
            }
        } else {
            $factura->update(['estado' => true]);
            $this->registrarPagoMensualidad($factura->id, $factura->valor, $request, $url);
            $this->userService->confirmarPago($factura->user_id, $factura->id, "Aprobado");
        }

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
