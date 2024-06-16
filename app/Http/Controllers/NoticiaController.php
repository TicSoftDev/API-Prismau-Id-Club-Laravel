<?php

namespace App\Http\Controllers;

use App\Models\Noticia;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class NoticiaController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function noticias()
    {
        $noticias = Noticia::all();
        return response()->json($noticias);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function crearNoticia(Request $request)
    {
        $noticia = new Noticia([
            "Titulo" => $request->Titulo,
            "Descripcion" => $request->Descripcion,
            "Vencimiento" => $request->Vencimiento,
        ]);

        if ($request->hasFile('Imagen')) {
            $imagen = $request->file('Imagen');
            $nameImage = Str::random(10) . '.' . $imagen->getClientOriginalExtension();
            $imagen = $imagen->storeAs('public/noticias', $nameImage);
            $url = Storage::url($imagen);
            $noticia->Imagen = $url;
        }
        $res = $noticia->save();

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
     * Store a newly created resource in storage.
     */
    public function Contnoticias()
    {
        $noticias = Noticia::count();
        return response()->json($noticias);
    }

    /**
     * Display the specified resource.
     */
    public function show(Noticia $noticia)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Noticia $noticia)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function actualizarNoticia(Request $request, $id)
    {
        $noticia = Noticia::find($id);
        $noticia->Titulo = $request->Titulo;
        $noticia->Descripcion = $request->Descripcion;
        $noticia->Vencimiento = $request->Vencimiento;

        // $imagen = $request->file('Imagen');
        // if ($imagen) {
        //     $nameImage = Str::random(10) . time() . '.' . $imagen->getClientOriginalExtension();
        //     $imagen = $imagen->storeAs('public/noticias', $nameImage);
        //     $url = Storage::url($imagen);
        //     $noticia->Imagen = $url;
        // }
        // if ($noticia->Imagen) {
        //     Storage::disk('local')->delete(str_replace('/storage', 'public', $noticia->Imagen));
        // }
        $rest = $noticia->save();
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

    /**
     * Remove the specified resource from storage.
     */
    public function eliminarNoticia($id)
    {
        $noticia = Noticia::findOrFail($id);
        $noticia->delete();
        return response()->json([
            "status" => true,
            "message" => "hecho"
        ], 200);
    }
}
