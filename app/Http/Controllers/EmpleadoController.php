<?php

namespace App\Http\Controllers;

use App\Http\Requests\PersonalRequest;
use App\Models\Empleado;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class EmpleadoController extends Controller
{

    public function validarEmpleado($request, $userId = null)
    {
        $rules = [
            'Nombre' => 'required',
            'Apellidos' => 'required',
            'Correo' => 'required|email',
            'Telefono' => 'required',
            'FechaNacimiento' => 'required',
            'LugarNacimiento' => 'required',
            'TipoDocumento' => 'required',
            'Documento' => 'required|unique:users,Documento' . ($userId ? ',' . $userId : ''),
            'Sexo' => 'required',
            'DireccionResidencia' => 'required',
            'CiudadResidencia' => 'required',
            'EstadoCivil' => 'required',
            'Cargo' => 'required',
            'Estado' => 'required',
        ];

        if (!$userId) {
            $rules['Rol'] = 'required';
        }

        $messages = [
            'Nombre.required' => 'El Nombre es obligatorio.',
            'Apellidos.required' => 'Los Apellidos son obligatorio.',
            'Correo.required' => 'El Correo es obligatorio.',
            'Correo.email' => 'El Correo no tiene un formato válido.',
            'Correo.unique' => 'El Correo ya está registrado en el sistema.',
            'Telefono.required' => 'El Telefono es obligatorio.',
            'FechaNacimiento.required' => 'La Fecha Nacimiento es obligatorio.',
            'LugarNacimiento.required' => 'El Lugar Nacimiento es obligatorio.',
            'TipoDocumento.required' => 'El Tipo Documento es obligatorio.',
            'Documento.required' => 'El Documento es obligatorio.',
            'Documento.unique' => 'El Documento ya está registrado en el sistema.',
            'Sexo.required' => 'El Sexo es obligatorio.',
            'DireccionResidencia.required' => 'La Direccion Residencia es obligatorio.',
            'CiudadResidencia.required' => 'La Ciudad Residencia es obligatorio.',
            'EstadoCivil.required' => 'El Estado Civil es obligatorio.',
            'Cargo.required' => 'El Cargo es obligatorio.',
            'Estado.required' => 'El Estado es obligatorio.',
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

    public function crearEmpleado(Request $request)
    {
        $validation = $this->validarEmpleado($request);
        if (!$validation['status']) return $validation;

        DB::beginTransaction();
        try {
            $user = User::create([
                'Documento' => $request->Documento,
                'password' => Hash::make($request->Documento),
                'Rol' => $request->Rol
            ]);

            $empleado = Empleado::create([
                'user_id' => $user->id,
                'Nombre' => $request->Nombre,
                'Apellidos' => $request->Apellidos,
                'Correo' => $request->Correo,
                'Telefono' => $request->Telefono,
                'FechaNacimiento' => $request->FechaNacimiento,
                'LugarNacimiento' => $request->LugarNacimiento,
                'TipoDocumento' => $request->TipoDocumento,
                'Documento' => $request->Documento,
                'Sexo' => $request->Sexo,
                'DireccionResidencia' => $request->DireccionResidencia,
                'CiudadResidencia' => $request->CiudadResidencia,
                'EstadoCivil' => $request->EstadoCivil,
                'Cargo' => $request->Cargo,
                'Estado' => $request->Estado,
            ]);

            DB::commit();
            return response()->json([
                "status" => true,
                "message" => "Empleado creado con exito",
                "empleado" => $empleado
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                "message" => "Error al actualizar: " . $e->getMessage()
            ], 500);
        }
    }

    public function empleados()
    {
        $empleados = Empleado::with('user')->get()->sortBy('Nombre');
        return response()->json($empleados->values()->all());
    }

    public function cantidadEmpleados()
    {
        $empleado = Empleado::count();
        return response()->json($empleado);
    }

    public function actualizarEmpleado(Request $request, string $id)
    {
        $validation = $this->validarEmpleado($request, $id);
        if (!$validation['status']) return $validation;
        DB::beginTransaction();
        try {
            $usuario = User::findOrFail($id);
            $empleado = $usuario->empleado;
            if ($usuario->Documento != $request->Documento || $usuario->Rol != $request->Rol) {
                $usuario->update([
                    'Documento' => $request->Documento,
                    'password' => Hash::make($request->Documento),
                    'Rol' => $request->Rol
                ]);
            }
            $empleado->update([
                "Nombre" => $request->Nombre,
                "Apellidos" => $request->Apellidos,
                "Correo" => $request->Correo,
                "Telefono" => $request->Telefono,
                "FechaNacimiento" => $request->FechaNacimiento,
                "LugarNacimiento" => $request->LugarNacimiento,
                "TipoDocumento" => $request->TipoDocumento,
                "Documento" => $request->Documento,
                "Sexo" => $request->Sexo,
                "DireccionResidencia" => $request->DireccionResidencia,
                "CiudadResidencia" => $request->CiudadResidencia,
                "EstadoCivil" => $request->EstadoCivil,
                "Cargo" => $request->Cargo,
                "Estado" => $request->Estado,
            ]);
            DB::commit();
            return response()->json([
                "status" => true,
                "message" => "Empleado actualizado con exito",
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                "message" => "Error al actualizar: " . $e->getMessage()
            ], 500);
        }
    }

    public function changeImagen(Request $request, $id)
    {
        $persona = Empleado::find($id);
        $imagen = $request->file('imagen');
        $nameImage = Str::slug($persona->Documento) . '_' . time() . '.' . $imagen->getClientOriginalExtension();
        $imagen = $imagen->storeAs('public/empleados', $nameImage);
        $url = Storage::url($imagen);

        if ($persona->imagen) {
            Storage::disk('local')->delete(str_replace('/storage', 'public', $persona->imagen));
        }

        $persona->imagen = $url;
        $rest = $persona->save();
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

    public function eliminarEmpleado(string $id)
    {
        $empleado = User::find($id);
        if (is_null($empleado)) {
            return response()->json(["message" => "no encontrado"], 404);
        }
        if ($empleado->empleado->imagen) {
            Storage::disk('local')->delete(str_replace('/storage', 'public', $empleado->empleado->imagen));
        }
        $empleado->empleado->delete();
        $empleado->delete();
        return response()->json([
            "status" => true,
            "message" => "hecho"
        ], 200);
    }
}
