<?php

namespace App\Http\Controllers;

use App\Models\Familiar;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class FamiliarController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(string $id)
    {
        $familiar = Familiar::where('personal_id', $id)->get();
        return response()->json($familiar);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $familiar = Familiar::all()->count();
        return response()->json($familiar);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        DB::beginTransaction();
        try {
            $user = User::create([
                'Documento' => $request->Documento,
                'password' => Hash::make($request->Documento),
                'Rol' => $request->Rol
            ]);

            $familiar = new Familiar();
            $familiar->user_id = $user->id;
            $familiar->personal_id = $request->personal_id;
            $familiar->Nombre = $request->Nombre;
            $familiar->Apellidos = $request->Apellidos;
            $familiar->Correo = $request->Correo;
            $familiar->Telefono = $request->Telefono;
            $familiar->FechaNacimiento = $request->FechaNacimiento;
            $familiar->LugarNacimiento = $request->LugarNacimiento;
            $familiar->TipoDocumento = $request->TipoDocumento;
            $familiar->Documento = $request->Documento;
            $familiar->Sexo = $request->Sexo;
            $familiar->DireccionResidencia = $request->DireccionResidencia;
            $familiar->CiudadResidencia = $request->CiudadResidencia;
            $familiar->EstadoCivil = $request->EstadoCivil;
            $familiar->Cargo = $request->Cargo;
            $familiar->Parentesco = $request->Parentesco;
            $familiar->Estado = 1;
            $familiar->save();

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
    public function show(String $id)
    {
        $familiar = Familiar::where('personal_id', $id)->get()->count();
        return response()->json($familiar);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(String $id)
    {
        $familiar = Familiar::where('personal_id', $id)->get();
        return response()->json($familiar);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        DB::beginTransaction();
        try {
            $usuario = User::find($id);
            $usuario->update([
                'Documento' => $request->Documento,
            ]);
            $res = $usuario->familiar->update([
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
                "Parentesco" => $request->Parentesco,
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
        $familiar = User::find($id);
        if (is_null($familiar)) {
            return response()->json(["message" => "no encontrado"], 404);
        }
        if ($familiar->familiar->imagen) {
            Storage::disk('local')->delete(str_replace('/storage', 'public', $familiar->familiar->imagen));
        }
        $familiar->familiar->delete();
        $familiar->delete();
        return response()->json(["message" => "hecho"], 200);
    }

    public function changeImagen(Request $request, $id)
    {
        $persona = Familiar::find($id);
        $imagen = $request->file('imagen');
        $nameImage = Str::slug($persona->Documento) . '_' . time() . '.' . $imagen->getClientOriginalExtension();
        $imagen = $imagen->storeAs('public/familiares', $nameImage);
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
