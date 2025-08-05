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
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Str;

class AdherenteController extends Controller
{

    public function validarAdherente($request, $userId = null, $asociadoId = null)
    {
        $rules = [
            'Nombre' => 'required',
            'Apellidos' => 'required',
            'TipoDocumento' => 'required',
            'Documento' => 'required|unique:users,Documento' . ($userId ? ',' . $userId : ''),
            'Codigo' => 'required|unique:adherentes,Codigo' . ($asociadoId ? ',' . $asociadoId : ''),
            'Correo' => 'required|email|unique:adherentes,Correo' . ($asociadoId ? ',' . $asociadoId : ''),
            'asociado_id' => 'required|exists:asociados,id|unique:adherentes,asociado_id' . ($asociadoId ? ',' . $asociadoId : ''),
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
            'asociado_id.required' => 'El Asociado es obligatorio.',
            'asociado_id.exists' => 'El Asociado no existe.',
            'asociado_id.unique' => 'El Asociado ya ha sido asignado.',
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

    public function crearAdherente(Request $request)
    {
        $validator = $this->validarAdherente($request);
        if (!$validator['status']) return $validator;
        DB::beginTransaction();
        try {
            $user = User::create([
                'Documento' => $request->Documento,
                'password' => Hash::make($request->Documento),
                'Rol' => $request->Rol
            ]);
            $adherente = new Adherente([
                'user_id' => $user->id,
                'asociado_id' => $request->asociado_id,
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
            $adherente->save();
            DB::commit();
            return response()->json([
                "status" => true,
                "message" => "Adherente creado con exito",
                "data" => $adherente
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                "status" => false,
                "message" => "Error en el servidor: " . $e->getMessage()
            ], 500);
        }
    }

    public function adherentes()
    {
        $adherentes = Adherente::withCount('familiares')->orderBy('Nombre')->get();
        return response()->json($adherentes->values()->all());
    }

    public function contAdherentes()
    {
        $adherentes = Adherente::count();
        return response()->json($adherentes);
    }

    public function adherenteConFamiliares(string $id)
    {
        $adherente = Adherente::with(['familiares' => function ($query) {
            $query->select('id', 'adherente_id', 'Nombre', 'Apellidos', 'parentesco');
        }])->select('id', 'imagen', 'Nombre', 'Apellidos', 'TipoDocumento', 'Documento')
            ->find($id);

        return response()->json($adherente);
    }

    public function actualizarAdherente(Request $request, string $id)
    {
        $adherente = Adherente::findOrFail($id);
        $validator = $this->validarAdherente($request, $adherente->user_id, $id);
        if (!$validator['status']) return $validator;

        DB::beginTransaction();
        try {
            $usuario = User::findOrFail($adherente->user_id);
            if ($adherente->asociado_id != $request->asociado_id) {
                $socio = $this->validarSocioUnico($request->asociado_id);
                if (!$socio['status']) return $socio;
            }
            if ($usuario->Documento != $request->Documento) {
                $usuario->update(['Documento' => $request->Documento,]);
            }
            $adherente->update([
                'asociado_id' => $request->asociado_id,
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
            foreach ($adherente->familiares as $familiar) {
                $familiar->update([
                    'Codigo' => $request->Codigo
                ]);
            }
            DB::commit();
            return response()->json([
                "status" => true,
                "message" => "Adherente actualizado correctamente"
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
            $adherente = Adherente::with('familiares')->findOrFail($id);
            $adherente->update(['Estado' => $request->Estado]);

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

            foreach ($adherente->familiares as $familiar) {
                $familiar->update(['Estado' => $request->Estado]);
            }

            Estados::create([
                'user_id' => $adherente->user_id,
                'Estado' => $estadoString,
                'Motivo' => $request->Motivo
            ]);

            // Mail::to($adherente->Correo)->send(new EstadosMail($estadoString, $request->Motivo));
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

    public function changeToAsociado(String $id)
    {
        DB::beginTransaction();
        try {
            $usuario = User::find($id);
            if (is_null($usuario)) {
                return response()->json(["message" => "no encontrado"], 404);
            }
            $adherente = $usuario->adherente;
            $asociado = Asociado::create([
                'user_id' => $usuario->id,
                'imagen' => $adherente->imagen,
                'Nombre' => $adherente->Nombre,
                'Apellidos' => $adherente->Apellidos,
                'TipoDocumento' => $adherente->TipoDocumento,
                'Documento' => $adherente->Documento,
                'Correo' => $adherente->Correo,
                'Telefono' => $adherente->Telefono,
                'FechaNacimiento' => $adherente->FechaNacimiento,
                'LugarNacimiento' => $adherente->LugarNacimiento,
                'Sexo' => $adherente->Sexo,
                'DireccionResidencia' => $adherente->DireccionResidencia,
                'CiudadResidencia' => $adherente->CiudadResidencia,
                'TiempoResidencia' => $adherente->TiempoResidencia,
                'EstadoCivil' => $adherente->EstadoCivil,
                'Profesion' => $adherente->Profesion,
                'Trabajo' => $adherente->Trabajo,
                'Cargo' => $adherente->Cargo,
                'TiempoServicio' => $adherente->TiempoServicio,
                'TelOficina' => $adherente->TelOficina,
                'DireccionOficina' => $adherente->DireccionOficina,
                'CiudadOficina' => $adherente->CiudadOficina,
                'Estado' => $adherente->Estado,
            ]);
            foreach ($adherente->familiares as $familiar) {
                $familiar->asociado_id = $asociado->id;
                $familiar->adherente_id = null;
                $familiar->save();
            }
            $adherente->delete();
            $usuario->update([
                'Rol' => 2,
            ]);

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
        $adherente = Adherente::find($id);
        $imagen = $request->file('imagen');
        $nameImage = Str::slug($adherente->Documento) . '_' . time() . '.' . $imagen->getClientOriginalExtension();
        $imagen = $imagen->storeAs('public/personal', $nameImage);
        $url = Storage::url($imagen);

        if ($adherente->imagen) {
            Storage::disk('local')->delete(str_replace('/storage', 'public', $adherente->imagen));
        }
        $adherente->imagen = $url;
        $rest = $adherente->save();
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

    public function eliminarAdherente(string $id)
    {
        $user = User::find($id);
        if (is_null($user)) {
            return response()->json(["message" => "no encontrado"], 404);
        }
        if ($user->adherente->imagen) {
            Storage::disk('local')->delete(str_replace('/storage', 'public', $user->adherente->imagen));
        }
        if ($user->adherente->familiares) {
            foreach ($user->adherente->familiares as $familiar) {
                if ($familiar->imagen) {
                    Storage::disk('local')->delete(str_replace('/storage', 'public', $familiar->imagen));
                }
                $usuarioFamiliar = User::find($familiar->user_id);
                $usuarioFamiliar->delete();
                $familiar->delete();
            }
        }
        $user->adherente->delete();
        $user->delete();
        return response()->json([
            "status" => true,
            "message" => "hecho"
        ], 200);
    }
}
