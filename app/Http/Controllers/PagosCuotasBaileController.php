<?php

namespace App\Http\Controllers;

use App\Models\PagosCuotasBaile;
use Illuminate\Http\Request;

class PagosCuotasBaileController extends Controller
{

    public function getPagos()
    {
        return PagosCuotasBaile::with([
            'cuota',
            'cuota.user',
            'cuota.user.asociado',
            'cuota.user.adherente'
        ])->get();
    }
}
