<?php

namespace App\services;

use App\Mail\pagoEmail;
use App\Models\CuotasBaile;
use App\Models\Mensualidades;
use App\Models\User;
use Carbon\Carbon;
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
        if ($user) {
            Mail::to($socio->Correo)->send(new pagoEmail($estado, $periodo, $mensualidad->valor));
        }
    }

    public function confirmarPagoBailes($id, $pago, $estado)
    {
        $user = User::where('id', $id)->with(['asociado', 'adherente'])->first();
        $cuota = CuotasBaile::where('id', $pago)->first();
        $socio = $user->asociado ?? $user->adherente;
        if ($user) {
            Mail::to($socio->Correo)->send(new pagoEmail($estado, $cuota->descripcion, $cuota->valor));
        }
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
}
