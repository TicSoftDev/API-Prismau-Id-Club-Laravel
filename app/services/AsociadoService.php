<?php

namespace App\services;

use App\Models\Asociado;

class AsociadoService
{

    public function getAsociados($request)
    {
        $query = Asociado::withCount('familiares');

        if ($request->filled('Nombre')) {
            $query->where('Nombre', 'like', '%' . $request->input('Nombre') . '%');
        }

        if ($request->filled('Apellidos')) {
            $query->where('Apellidos', 'like', '%' . $request->input('Apellidos') . '%');
        }

        if ($request->filled('Documento')) {
            $query->where('Documento', 'like', '%' . $request->input('Documento') . '%');
        }

        if ($request->filled('estado') && $request->input('estado') != 10) {
            $query->where('Estado', $request->input('estado'));
        }

        $query->orderBy('Nombre', 'asc');

        $limit = $request->input('limit', 30);
        $asociados = $query->paginate($limit);

        return response()->json([
            'data' => $asociados->items(),
            'total' => $asociados->total(),
            'page' => $asociados->currentPage(),
            'limit' => $asociados->perPage(),
        ]);
    }
}
