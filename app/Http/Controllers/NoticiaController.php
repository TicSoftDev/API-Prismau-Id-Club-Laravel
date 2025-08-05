<?php

namespace App\Http\Controllers;

use App\Jobs\EnviarNotificacionNoticia;
use App\Mail\EventosMail;
use App\Models\Noticia;
use App\Models\User;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class NoticiaController extends Controller
{

    public function validateNoticia($request)
    {
        $rules = [
            'Titulo' => 'required|filled',
            'Descripcion' => 'required|filled',
            'Vencimiento' => 'required|filled',
            'Destinatario' => 'required|filled',
            'Fecha' => 'required|filled',
            'Hora' => 'required|filled',
            'Tipo' => 'required|filled',
        ];

        $messages = [
            'Titulo.required' => 'El titulo es obligatorio.',
            'Descripcion.required' => 'La descripcion es obligatoria.',
            'Vencimiento.required' => 'El vencimiento es obligatorio.',
            'Destinatario' => 'El destinatario es obligatorio.',
            'Tipo.required' => 'El tipo es obligatorio.',
            'Hora.required' => 'La hora es obligatoria.',
            'Fecha.required' => 'La fecha es obligatoria.',
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

    public function crearNoticia(Request $request)
    {
        try {
            $validation = $this->validateNoticia($request);
            if (!$validation['status']) return $validation;

            $relacionesPorRol = [
                2 => 'asociado',
                3 => 'adherente',
                4 => 'familiar',
                5 => 'empleado',
                6 => 'empleado',
            ];

            $rolId = (int) $request->destinatario;
            $relacion = $relacionesPorRol[$rolId] ?? null;

            $noticia = new Noticia([
                "Titulo" => $request->Titulo,
                "Descripcion" => $request->Descripcion,
                "Vencimiento" => $request->Vencimiento,
                "Fecha" => $request->Fecha,
                "Hora" => $request->Hora,
                "Tipo" => $request->Tipo,
                "Correo" => $request->correo,
                "Push" => $request->push,
                "Destinatario" => $request->Destinatario,
            ]);

            if ($request->hasFile('Imagen')) {
                $imagen = $request->file('Imagen');
                $nameImage = Str::random(10) . '.' . $imagen->getClientOriginalExtension();
                $imagen = $imagen->storeAs('public/noticias', $nameImage);
                $url = Storage::url($imagen);
                $noticia->Imagen = $url;
            }

            $noticia->save();

            // $usuarios = User::where('Rol', $rolId)->with($relacion)->get();

            // if (filter_var($request->correo, FILTER_VALIDATE_BOOLEAN)) {
            //     foreach ($usuarios as $user) {
            //         $perfil = $user->{$relacion};
            //         if ($perfil && !empty($perfil->Correo)) {
            //             Log::info($perfil->Correo);
            //             Mail::to($perfil->Correo)->queue(new EventosMail($request->Titulo, $request->Descripcion));
            //         }
            //     }
            // }

            if ($request->push === true) {
                dispatch(new EnviarNotificacionNoticia($rolId, $noticia));
            }

            return response()->json([
                'status'  => true,
                'message' => 'Evento creado con éxito',
            ], 201);
        } catch (Exception $e) {
            return response()->json([
                "status" => false,
                "message" => "Error en el servidor: " . $e->getMessage()
            ], 500);
        }
    }

    public function noticias()
    {
        $noticias = Noticia::where('Vencimiento', '>', now())
            ->orderBy('created_at', 'desc')
            ->get();
        return response()->json($noticias);
    }

    public function Contnoticias()
    {
        $noticias = Noticia::count();
        return response()->json($noticias);
    }

    public function actualizarNoticia(Request $request, $id)
    {
        try {
            $validation = $this->validateNoticia($request);
            if (!$validation['status']) return $validation;

            $noticia = Noticia::find($id);
            $noticia->Titulo = $request->Titulo;
            $noticia->Descripcion = $request->Descripcion;
            $noticia->Vencimiento = $request->Vencimiento;
            $noticia->Fecha = $request->Fecha;
            $noticia->Hora = $request->Hora;
            $noticia->Tipo = $request->Tipo;

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

            $noticia->save();

            return response()->json([
                "status" => true,
                "message" => "Evento actualizado con éxito",
            ], 201);
        } catch (Exception $e) {
            return response()->json([
                "status" => false,
                "message" => "Error en el servidor: " . $e->getMessage()
            ],);
        }
    }

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
