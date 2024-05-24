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
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class EmpleadoController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function empleados()
    {
        $empleados = Empleado::all()
            ->sortBy('Nombre');
        return response()->json($empleados->values()->all());
    }

    /**
     * Show the form for creating a new resource.
     */
    public function cantidadEmpleados()
    {
        $empleado = Empleado::count();
        return response()->json($empleado);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function crearEmpleado(Request $request)
    {
        DB::beginTransaction();
        try {
            $user = User::create([
                'Documento' => $request->Documento,
                'password' => Hash::make($request->Documento),
                'Rol' => $request->Rol
            ]);

            $empleado = new Empleado();
            $empleado->user_id = $user->id;
            $empleado->Nombre = $request->Nombre;
            $empleado->Apellidos = $request->Apellidos;
            $empleado->Correo = $request->Correo;
            $empleado->Telefono = $request->Telefono;
            $empleado->FechaNacimiento = $request->FechaNacimiento;
            $empleado->LugarNacimiento = $request->LugarNacimiento;
            $empleado->TipoDocumento = $request->TipoDocumento;
            $empleado->Documento = $request->Documento;
            $empleado->Sexo = $request->Sexo;
            $empleado->DireccionResidencia = $request->DireccionResidencia;
            $empleado->CiudadResidencia = $request->CiudadResidencia;
            $empleado->EstadoCivil = $request->EstadoCivil;
            $empleado->Cargo = $request->Cargo;
            $empleado->Estado = $request->Estado;
            $empleado->save();

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
                "message" => "Error al actualizar: " . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Empleado $empleados)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Empleado $empleados)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function actualizarEmpleado(Request $request, string $id)
    {
        DB::beginTransaction();
        $usuario = User::findOrFail($id);
        $empleado = $usuario->empleado;
        try {
            $request->validate([
                'Documento' => 'required|string|max:255|unique:users,Documento,' . $usuario->id,
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'status' => false,
                'message' => 'Existe',
                'errors' => $e->errors(),
            ], 200);
        }
        try {
            if ($usuario->Documento != $request->Documento) {
                $usuario->update([
                    'Documento' => $request->Documento,
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
                "message" => "hecho"
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                "message" => "Error al actualizar: " . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
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
}
