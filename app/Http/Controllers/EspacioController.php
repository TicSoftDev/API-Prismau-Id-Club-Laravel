<?php

namespace App\Http\Controllers;

use App\Models\Espacio;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class EspacioController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function espacios()
    {
        $espacios = Espacio::all();
        return response()->json($espacios);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function contEspacios()
    {
        $espacios = Espacio::all()->count();
        return response()->json($espacios);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function crearEspacio(Request $request)
    {
        $espacio = new Espacio([
            "Descripcion" => $request->Descripcion,
            "Estado" => $request->Estado,
        ]);

        if ($request->hasFile('imagen')) {
            $imagen = $request->file('imagen');
            $nameImage = Str::slug($request->Descripcion) . '_' . time() . '.' . $imagen->getClientOriginalExtension();
            $imagen = $imagen->storeAs('public/espacios', $nameImage);
            $url = Storage::url($imagen);
            $espacio->imagen = $url;
        }
        $res = $espacio->save();

        if ($res) {
            return response()->json([
                "status" => true,
                "message" => "hecho"
            ], 201);
        } else {
            response()->json([
                "status" => false,
                "message" => "No se pudo agregar"
            ],);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Espacio $espacios)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Espacio $espacios)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function actualizarEspacio(Request $request, string $id)
    {
        $espacio = Espacio::find($id);
        $res = $espacio->update(([
            "Descripcion" => $request->Descripcion,
            "Estado" => $request->Estado,
        ]));
        if ($res) {
            return response()->json([
                "status" => true,
                "message" => "hecho"
            ], 201);
        } else {
            response()->json([
                "status" => false,
                "message" => "No se pudo actualizar"
            ],);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function eliminarEspacio(string $id)
    {
        $espacio = Espacio::find($id);
        if (is_null($espacio)) {
            return response()->json(["message" => "no encontrado"], 404);
        }
        if ($espacio->imagen) {
            Storage::disk('local')->delete(str_replace('/storage', 'public', $espacio->imagen));
        }
        $espacio->delete();
        return response()->json([
            "status" => true,
            "message" => "hecho"
        ], 200);
    }

    public function changeImagen(Request $request, $id)
    {
        $espacio = Espacio::find($id);
        $imagen = $request->file('imagen');
        $nameImage = Str::slug($espacio->Descripcion) . '_' . time() . '.' . $imagen->getClientOriginalExtension();
        $imagen = $imagen->storeAs('public/espacios', $nameImage);
        $url = Storage::url($imagen);

        if ($espacio->imagen) {
            Storage::disk('local')->delete(str_replace('/storage', 'public', $espacio->imagen));
        }

        $espacio->imagen = $url;
        $rest = $espacio->save();
        if ($rest > 0) {
            return response()->json([
                "status" => true,
                "message" => "hecho"
            ], 201);
        } else {
            response()->json([
                "status" => false,
                "message" => "No se pudo agregar"
            ],);
        }
    }

}
