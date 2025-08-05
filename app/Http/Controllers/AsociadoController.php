<?php

namespace App\Http\Controllers;

use App\Mail\EstadosMail;
use App\Models\Adherente;
use App\Models\Asociado;
use App\Models\Estados;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Str;

class AsociadoController extends Controller
{

    public function validarAsociado($request, $userId = null, $asociadoId = null)
    {
        $rules = [
            'Nombre' => 'required',
            'Apellidos' => 'required',
            'TipoDocumento' => 'required',
            'Documento' => 'required|unique:users,Documento' . ($userId ? ',' . $userId : ''),
            'Codigo' => 'required|unique:asociados,Codigo' . ($asociadoId ? ',' . $asociadoId : ''),
            'Correo' => 'required|email|unique:asociados,Correo' . ($asociadoId ? ',' . $asociadoId : ''),
            'Telefono' => 'required',
            'Sexo' => 'required',
        ];

        if (!$userId) {
            $rules['Rol'] = 'required';
        }

        $messages = [
            'Nombre.required' => 'El Nombre es obligatorio.',
            'Apellidos.required' => 'Los Apellidos son obligatorio.',
            'TipoDocumento.required' => 'El Tipo Documento es obligatorio.',
            'Documento.required' => 'El Documento es obligatorio.',
            'Documento.unique' => 'El Documento ya está registrado en el sistema.',
            'Correo.required' => 'El Correo es obligatorio.',
            'Correo.email' => 'El Correo no tiene un formato válido.',
            'Correo.unique' => 'El Correo ya está registrado en el sistema.',
            'Codigo.required' => 'El Codigo es obligatorio.',
            'Codigo.unique' => 'El Codigo ya está registrado en el sistema.',
            'Telefono.required' => 'El Telefono es obligatorio.',
            'Sexo.required' => 'El Sexo es obligatorio.',
            'Rol.required' => 'El Rol es obligatorio.',
        ];

        $validator = Validator::make($request->all(), $rules, $messages);

        if ($validator->fails()) {
            return [
                'status' => false,
                'errors' => $validator->errors()->all()
            ];
        }

        return ['status' => true];
    }

    public function crearAsociado(Request $request)
    {
        $validator = $this->validarAsociado($request);
        if (!$validator['status']) return $validator;
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
                "message" => "Asociado creado con exito",
                "data" => $asociado
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                "message" => "Error al crear: " . $e->getMessage()
            ], 500);
        }
    }

    public function asociados()
    {
        $asociados = Asociado::withCount('familiares')->orderBy('Nombre')->get();
        return response()->json($asociados->values()->all());
    }

    public function cantidadAsociados()
    {
        $asociados = Asociado::count();
        return response()->json($asociados);
    }

    public function asociadoConFamiliares(string $id)
    {
        $asociado = Asociado::with(['familiares' => function ($query) {
            $query->select('id', 'asociado_id', 'Nombre', 'Apellidos', 'parentesco');
        }])->select('id', 'imagen', 'Nombre', 'Apellidos', 'TipoDocumento', 'Documento', 'Estado')
            ->find($id);

        return response()->json($asociado);
    }

    public function actualizarAsociado(Request $request, string $id)
    {
        $asociado = Asociado::findOrFail($id);
        $userId = $asociado->user_id;
        $asociadoId = $asociado->id;

        $validator = $this->validarAsociado($request, $userId, $asociadoId);
        if (!$validator['status']) return $validator;

        DB::beginTransaction();
        try {
            $usuario = User::findOrFail($userId);
            $asociado = $usuario->asociado;
            if ($usuario->Documento != $request->Documento) {
                $usuario->update(['Documento' => $request->Documento,]);
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
            if ($asociado->Codigo != $request->Codigo) {
                foreach ($asociado->familiares as $familiar) {
                    $familiar->update(['Codigo' => $request->Codigo]);
                }
            }
            DB::commit();
            return response()->json([
                "status" => true,
                "message" => "Asociado actualizado con exito",
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                "message" => "Error en el servidor: " . $e->getMessage()
            ], 500);
        }
    }

    public function changeStatus(String $id, Request $request)
    {
        DB::beginTransaction();
        try {
            $asociado = Asociado::with('familiares')->findOrFail($id);
            $asociado->update(['Estado' => $request->Estado]);

            if ($request->Estado == 0) {
                $estadoString = "Inactivo";
            } else if ($request->Estado == 1) {
                $estadoString = "Activo";
            } else if ($request->Estado == 2) {
                $estadoString = "Retirado";
            } else if ($request->Estado == 3) {
                $estadoString = "Mora";
            } else {
                $estadoString = "Retirado en mora";
            }

            foreach ($asociado->familiares as $familiar) {
                $familiar->update(['Estado' => $request->Estado]);
            }

            $adherente = Adherente::with('familiares')->where('asociado_id', $id)->first();
            if ($adherente) {
                $adherente->update(['Estado' => $request->Estado]);
                foreach ($adherente->familiares as $familiar) {
                    $familiar->update(['Estado' => $request->Estado]);
                }
            }

            Estados::create([
                'user_id' => $asociado->user_id,
                'Estado' => $estadoString,
                'Motivo' => $request->Motivo
            ]);

            // Mail::to($asociado->Correo)->send(new EstadosMail($estadoString, $request->Motivo));
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
}
