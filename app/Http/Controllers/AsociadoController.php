<?php

namespace App\Http\Controllers;

use App\Mail\EstadosMail;
use App\Models\Adherente;
use App\Models\Asociado;
use App\Models\Estados;
use App\Models\Personal;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Str;

class AsociadoController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function asociadosActivos()
    {
        $asociados = Asociado::withCount('familiares')
            ->where('Estado', 1)
            ->get()
            ->sortBy('Nombre');
        return response()->json($asociados->values()->all());
    }

    /**
     * Show the form for creating a new resource.
     */
    public function cantidadAsociados()
    {
        $asociados = Asociado::count();
        return response()->json($asociados);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function crearAsociado(Request $request)
    {
        DB::beginTransaction();
        try {
            $user = User::create([
                'Documento' => $request->Documento,
                'password' => Hash::make($request->Documento),
                'Rol' => $request->Rol
            ]);
            $asociado = new Asociado([
                'user_id' => $user->id,
                'Nombre' => $request->Nombre,
                'Apellidos' => $request->Apellidos,
                'TipoDocumento' => $request->TipoDocumento,
                'Documento' => $request->Documento,
                'Correo' => $request->Correo,
                'Telefono' => $request->Telefono,
                'FechaNacimiento' => $request->FechaNacimiento,
                'LugarNacimiento' => $request->LugarNacimiento,
                'Sexo' => $request->Sexo,
                'Codigo' => $request->Codigo,
                'DireccionResidencia' => $request->DireccionResidencia,
                'CiudadResidencia' => $request->CiudadResidencia,
                'TiempoResidencia' => $request->TiempoResidencia,
                'EstadoCivil' => $request->EstadoCivil,
                'Profesion' => $request->Profesion,
                'Trabajo' => $request->Trabajo,
                'Cargo' => $request->Cargo,
                'TiempoServicio' => $request->TiempoServicio,
                'TelOficina' => $request->TelOficina,
                'DireccionOficina' => $request->DireccionOficina,
                'CiudadOficina' => $request->CiudadOficina,
                'Estado' => 1
            ]);
            $asociado->save();
            DB::commit();
            return response()->json([
                "status" => true,
                "message" => "hecho"
            ], 201);
        } catch (QueryException $e) {
            DB::rollBack();
            $errorCode = $e->errorInfo[1];
            if ($errorCode == 1062) {
                return response()->json([
                    "status" => false,
                    "message" => "Existe"
                ], 200);
            }
            return response()->json([
                "status" => false,
                "message" => "No se pudo agregar, error: " . $e->getMessage()
            ], 500);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                "message" => "Error en el servidor: " . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function asociadosInactivos()
    {
        $asociados = Asociado::withCount('familiares')
            ->where('Estado', 0)
            ->get()
            ->sortBy('Nombre');
        return response()->json($asociados->values()->all());
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function asociadoConFamiliares(string $id)
    {
        $asociado = Asociado::with(['familiares' => function ($query) {
            $query->select('id', 'asociado_id', 'Nombre', 'Apellidos', 'parentesco');
        }])->select('id', 'imagen', 'Nombre', 'Apellidos', 'TipoDocumento', 'Documento')
            ->find($id);

        return response()->json($asociado);
    }

    /**
     * Update the specified resource in storage.
     */
    public function actualizarAsociado(Request $request, string $id)
    {
        $usuario = User::findOrFail($id);
        $asociado = $usuario->asociado;
        try {
            $request->validate([
                'Documento' => 'required|string|max:255|unique:users,Documento,' . $usuario->id,
                'Correo' => 'required|email|max:255|unique:asociados,Correo,' . $asociado->id,
                'Codigo' => 'required|string|max:255|unique:asociados,Codigo,' . $asociado->id,
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'status' => false,
                'message' => 'Existe',
                'errors' => $e->errors(),
            ], 200);
        }
        DB::beginTransaction();
        try {
            if ($usuario->Documento != $request->Documento) {
                $usuario->update([
                    'Documento' => $request->Documento,
                ]);
            }
            $asociado->update([
                "Nombre" => $request->Nombre,
                "Apellidos" => $request->Apellidos,
                "TipoDocumento" => $request->TipoDocumento,
                "Documento" => $request->Documento,
                "Correo" => $request->Correo,
                "Telefono" => $request->Telefono,
                "FechaNacimiento" => $request->FechaNacimiento,
                "LugarNacimiento" => $request->LugarNacimiento,
                "Sexo" => $request->Sexo,
                'Codigo' => $request->Codigo,
                "DireccionResidencia" => $request->DireccionResidencia,
                "CiudadResidencia" => $request->CiudadResidencia,
                "TiempoResidencia" => $request->TiempoResidencia,
                "EstadoCivil" => $request->EstadoCivil,
                "Profesion" => $request->Profesion,
                "Trabajo" => $request->Trabajo,
                "Cargo" => $request->Cargo,
                "TiempoServicio" => $request->TiempoServicio,
                "TelOficina" => $request->TelOficina,
                "DireccionOficina" => $request->DireccionOficina,
                "CiudadOficina" => $request->CiudadOficina,
            ]);
            foreach ($asociado->familiares as $familiar) {
                $familiar->update(['Codigo' => $request->Codigo]);
            }
            DB::commit();
            return response()->json([
                "status" => true,
                "message" => "hecho"
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                "message" => "Error en el servidor: " . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function eliminarAsociado(string $id)
    {
        $user = User::find($id);
        if (is_null($user)) {
            return response()->json(["message" => "no encontrado"], 404);
        }
        if ($user->asociado->imagen) {
            Storage::disk('local')->delete(str_replace('/storage', 'public', $user->asociado->imagen));
        }
        if ($user->asociado->familiares) {
            foreach ($user->asociado->familiares as $familiar) {
                if ($familiar->imagen) {
                    Storage::disk('local')->delete(str_replace('/storage', 'public', $familiar->imagen));
                }
                $usuarioFamiliar = User::find($familiar->user_id);
                $usuarioFamiliar->delete();
                $familiar->delete();
            }
        }
        $user->asociado->delete();
        $user->delete();
        return response()->json([
            "status" => true,
            "message" => "hecho"
        ], 200);
    }

    public function changeStatus(String $id, Request $request)
    {
        DB::beginTransaction();
        try {
            $asociado = Asociado::with('familiares')->findOrFail($id);
            $nuevoEstado = $asociado->Estado == 0 ? 1 : 0;
            $estadoString = $nuevoEstado == 1 ? "Activo" : "Inactivo";
            $asociado->update(['Estado' => $nuevoEstado]);

            foreach ($asociado->familiares as $familiar) {
                $familiar->update(['Estado' => $nuevoEstado]);
            }

            $adherente = Adherente::with('familiares')->where('asociado_id', $id)->first();
            if ($adherente) {
                $adherente->update(['Estado' => $nuevoEstado]);
                foreach ($adherente->familiares as $familiar) {
                    $familiar->update(['Estado' => $nuevoEstado]);
                }
            }

            Estados::create([
                'user_id' => $asociado->user_id,
                'Estado' => $estadoString,
                'Motivo' => $request->Motivo
            ]);
            $fecha = now()->format('d/m/Y'); 
            $content = <<<HTML
                        <h1>Club Sincelejo</h1>
                        <p><strong>Fecha:</strong> {$fecha}</p>
                        <h3>Cordial saludo,</h3>
                        <p>Estimado(a) socio(a),</p>
                        <p>Queremos informarle que su estado en el Club Sincelejo ha sido cambiado a <strong>{$estadoString}</strong>.</p>
                        <p><strong>Motivo:</strong> {$request->Motivo}</p>
                        <p>En caso de inquietudes, no dude en contactar a la gerencia del club.</p>
                        <p>Atentamente,<br>
                        Gerencia<br>
                        Club Sincelejo</p>
                        HTML;
            Mail::to($asociado->Correo)->send(new EstadosMail($content));
            DB::commit();
            return response()->json([
                "status" => true,
                "message" => "Cambio de estado realizado con éxito"
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                "status" => false,
                "message" => "Error en el servidor: " . $e->getMessage()
            ], 500);
        }
    }

    public function changeToAdherente(String $id)
    {
        DB::beginTransaction();
        try {
            $usuario = User::find($id);
            if (is_null($usuario)) {
                return response()->json(["message" => "no encontrado"], 404);
            }

            $asociado = $usuario->asociado;

            Adherente::create([
                'user_id' => $usuario->id,
                'imagen' => $asociado->imagen,
                'Nombre' => $asociado->Nombre,
                'Apellidos' => $asociado->Apellidos,
                'TipoDocumento' => $asociado->TipoDocumento,
                'Documento' => $asociado->Documento,
                'Correo' => $asociado->Correo,
                'Telefono' => $asociado->Telefono,
                'FechaNacimiento' => $asociado->FechaNacimiento,
                'LugarNacimiento' => $asociado->LugarNacimiento,
                'Sexo' => $asociado->Sexo,
                'DireccionResidencia' => $asociado->DireccionResidencia,
                'CiudadResidencia' => $asociado->CiudadResidencia,
                'TiempoResidencia' => $asociado->TiempoResidencia,
                'EstadoCivil' => $asociado->EstadoCivil,
                'Profesion' => $asociado->Profesion,
                'Trabajo' => $asociado->Trabajo,
                'Cargo' => $asociado->Cargo,
                'TiempoServicio' => $asociado->TiempoServicio,
                'TelOficina' => $asociado->TelOficina,
                'DireccionOficina' => $asociado->DireccionOficina,
                'CiudadOficina' => $asociado->CiudadOficina,
                'Estado' => $asociado->Estado,
            ]);

            $usuario->update([
                'Rol' => 3,
            ]);

            $asociado->delete();

            DB::commit();
            return response()->json([
                "status" => true,
                "message" => "hecho"
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                "status" => false,
                "message" => "Error en el servidor: " . $e->getMessage()
            ], 500);
        }
    }

    public function changeImagen(Request $request, $id)
    {
        $asociado = Asociado::find($id);
        $imagen = $request->file('imagen');
        $nameImage = Str::slug($asociado->Documento) . '_' . time() . '.' . $imagen->getClientOriginalExtension();
        $imagen = $imagen->storeAs('public/personal', $nameImage);
        $url = Storage::url($imagen);

        if ($asociado->imagen) {
            Storage::disk('local')->delete(str_replace('/storage', 'public', $asociado->imagen));
        }
        $asociado->imagen = $url;
        $rest = $asociado->save();
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
