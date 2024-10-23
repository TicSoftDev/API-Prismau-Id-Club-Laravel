<?php

namespace App\services;

use App\Mail\pagoEmail;
use App\Models\Mensualidades;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Mail;

class UserService
{

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
}
