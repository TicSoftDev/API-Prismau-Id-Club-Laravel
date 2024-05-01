<?php

namespace App\Http\Controllers;

use App\Http\Requests\PersonalRequest;
use App\Models\Empleado;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class EmpleadoController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $empleados = User::whereIn('Rol', ['4', '6'])
            ->with('empleado')
            ->get()
            ->sortBy('empleado.Nombre');

        return response()->json($empleados->values()->all());
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $empleado = User::whereIn('Rol', ['4', '6'])->count();
        return response()->json($empleado);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(PersonalRequest $request)
    {
        DB::beginTransaction();
        try {
            $request->validated();

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
                "message" => "hecho"
            ], 201);
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
    public function update(PersonalRequest $request, string $id)
    {
        DB::beginTransaction();
        try {
            $usuario = User::findOrFail($id);
            $usuario->update([
                'Documento' => $request->Documento,
            ]);
            $usuario->empleado->update([
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
                "message" => "hecho"
            ], 201);
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
    public function destroy(string $id)
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
        return response()->json(["message" => "hecho"], 200);
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
                "message" => "hecho"
            ], 201);
        } else {
            response()->json([
                "message" => "No se pudo agregar"
            ],);
        }
    }
}
