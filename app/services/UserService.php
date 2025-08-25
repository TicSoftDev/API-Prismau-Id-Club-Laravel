<?php

namespace App\services;

use App\Mail\pagoEmail;
use App\Models\CuotasBaile;
use App\Models\Mensualidades;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

class UserService
{

    public function getSociosConPrecios()
    {
        $socios = User::with(['asociado', 'adherente', 'mensualidades', 'cuotas'])
            ->whereIn('Rol', [2, 3])->get();

        $filteredUsers = $socios->map(function ($user) {
            $info = $user->asociado ?? $user->adherente;

            $ultimaMensualidad = $user->mensualidades->last();
            $ultimaCuotaBaile = $user->cuotas->last();

            return [
                'id' => $user->id,
                'imagen' => $info->imagen ?? null,
                'nombre' => $info ? $info->Nombre : null,
                'apellidos' => $info ? $info->Apellidos : null,
                'tipoDocumento' => $info ? $info->TipoDocumento : null,
                'documento' => $info ? $info->Documento : null,
                'codigo' => $info ? $info->Codigo : null,
                'estado' => $info ? $info->Estado : null,
                'rol' => $user->Rol,
                'mensualidad' => $ultimaMensualidad ? $ultimaMensualidad->valor : null,
                'cuota_baile' => $ultimaCuotaBaile ? $ultimaCuotaBaile->valor : null
            ];
        });

        return $filteredUsers;
    }

    public function getSaldosSocios()
    {
        $socios = User::with([
            'asociado:id,user_id,Nombre,Apellidos,TipoDocumento,Documento,Estado',
            'adherente:id,user_id,Nombre,Apellidos,TipoDocumento,Documento,Estado',
        ])
            ->withCount([
                'mensualidades as meses_mensualidad_pendientes' => function ($q) {
                    $q->where('estado', 0);
                },
                'cuotas as meses_cuota_baile_pendientes' => function ($q) {
                    $q->where('estado', 0);
                },
            ])
            ->whereIn('Rol', [2, 3])
            ->get(['id', 'Rol']);

        return $socios->map(function ($user) {
            $info = $user->asociado ?? $user->adherente;

            return [
                'id' => $user->id,
                'nombreCompleto' => trim(($info->Nombre ?? '') . ' ' . ($info->Apellidos ?? '')) ?: 'No disponible',
                'documento'  =>  $info->Documento ?? null,
                'estado' => $info->Estado ?? null,
                'rol' => $user->Rol,
                'mensualidades' => (int) $user->meses_mensualidad_pendientes,
                'cuotas' => (int) $user->meses_cuota_baile_pendientes,
            ];
        })->values();
    }

    public function cambiarEstado($id, $estado)
    {
        $user = User::where('id', $id)->with(['asociado', 'adherente'])->first();
        if ($user) {
            $socio = $user->asociado ?? $user->adherente;
            $socio->Estado = $estado;
            return $socio->save();
        }
    }

    public function confirmarPago($id, $pago, $estado)
    {
        $user = User::where('id', $id)->with(['asociado', 'adherente'])->first();
        $mensualidad = Mensualidades::where('id', $pago)->first();
        $periodo = Carbon::parse($mensualidad->fecha)->translatedFormat('F \d\e Y');
        $socio = $user->asociado ?? $user->adherente;
        // if ($user) {
        // Mail::to($socio->Correo)->send(new pagoEmail($estado, $periodo, $mensualidad->valor));
        // }
    }

    public function confirmarPagoBailes($id, $pago, $estado)
    {
        $user = User::where('id', $id)->with(['asociado', 'adherente'])->first();
        $cuota = CuotasBaile::where('id', $pago)->first();
        $socio = $user->asociado ?? $user->adherente;
        // if ($user) {
        // Mail::to($socio->Correo)->send(new pagoEmail($estado, $cuota->descripcion, $cuota->valor));
        // }
    }

    public function resetPassword($id)
    {
        $user = User::where('id', $id)->first();
        $user->password = Hash::make($user->Documento);
        $user->save();
        return response()->json([
            "status" => true,
            "message" => "hecho"
        ]);
    }

    public function getContabilidadGeneral()
    {
        // ==== INGRESOS ====
        $ingresosMensualidades = (float) DB::table('pagos')->sum('monto');
        $ingresosCuotas        = (float) DB::table('pagos_cuotas_bailes')->sum('monto');
        $ingresosTotal         = $ingresosMensualidades + $ingresosCuotas;

        // ==== PENDIENTES (saldo real = valor - SUM(pagos)) ====
        $pendMens = DB::table('mensualidades as m')
            ->leftJoin(
                DB::raw('(SELECT mensualidad_id, SUM(monto) AS pagado FROM pagos GROUP BY mensualidad_id) p'),
                'p.mensualidad_id',
                '=',
                'm.id'
            )
            ->selectRaw("
            SUM(CASE WHEN GREATEST(m.valor - COALESCE(p.pagado,0), 0) > 0 THEN 1 ELSE 0 END) AS pendientes,
            SUM(GREATEST(m.valor - COALESCE(p.pagado,0), 0)) AS monto_pendiente
        ")
            ->first();

        $pendCuotas = DB::table('cuotas_bailes as c')
            ->leftJoin(
                DB::raw('(SELECT cuotas_baile_id, SUM(monto) AS pagado FROM pagos_cuotas_bailes GROUP BY cuotas_baile_id) pc'),
                'pc.cuotas_baile_id',
                '=',
                'c.id'
            )
            ->selectRaw("
            SUM(CASE WHEN GREATEST(c.valor - COALESCE(pc.pagado,0), 0) > 0 THEN 1 ELSE 0 END) AS pendientes,
            SUM(GREATEST(c.valor - COALESCE(pc.pagado,0), 0)) AS monto_pendiente
        ")
            ->first();

        return response()->json([
            'resumen' => [
                'ingresos' => [
                    'mensualidades' => $ingresosMensualidades,
                    'cuotas_baile'  => $ingresosCuotas,
                    'total'         => $ingresosTotal,
                ],
                'pendientes' => [
                    'mensualidades' => [
                        'registros' => (int) ($pendMens->pendientes ?? 0),
                        'monto'     => (float) ($pendMens->monto_pendiente ?? 0),
                    ],
                    'cuotas_baile' => [
                        'registros' => (int) ($pendCuotas->pendientes ?? 0),
                        'monto'     => (float) ($pendCuotas->monto_pendiente ?? 0),
                    ],
                ],
            ],
        ]);
    }
}
