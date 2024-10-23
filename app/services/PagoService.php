<?php

namespace App\services;

use App\Models\Pagos;

class PagoService
{

    public function crear(array $data)
    {
        return Pagos::create($data);
    }
}
