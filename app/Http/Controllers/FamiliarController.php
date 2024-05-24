<?php

namespace App\Http\Controllers;

use App\Models\Familiar;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class FamiliarController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function familiaresAsociado($id,  $rol)
    {
        if ($rol == "Adherente") {
            $familiar = Familiar::where('adherente_id', $id)->get();
        } else {
            $familiar = Familiar::where('asociado_id', $id)->get();
        }
        return response()->json($familiar);
    }

    public function familiaresAdherentes(string $id)
    {
        $familiar = Familiar::where('adherente_id', $id)->get();
        return response()->json($familiar);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function contFamiliares()
    {
        $familiar = Familiar::count();
        return response()->json($familiar);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function crearFamiliaresAsociado(Request $request)
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
            $familiar->asociado_id = $request->asociado_id;
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

    public function crearFamiliaresAdherente(Request $request)
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
            $familiar->adherente_id = $request->adherente_id;
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
    public function contFamiliaresAsociado(String $id,  $rol)
    {
        if ($rol == "Adherente") {
            $familiar = Familiar::where('adherente_id', $id)->get()->count();
        } else {
            $familiar = Familiar::where('asociado_id', $id)->get()->count();
        }
        return response()->json($familiar);
    }

    /**
     * Update the specified resource in storage.
     */
    public function actualizarFamiliar(Request $request, string $id)
    {
        try {
            $request->validate([
                'Documento' => 'required|string|max:255|unique:users,Documento,' . $id,
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
                "status" => true,
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
    public function eliminarFamiliar(string $id)
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
        return response()->json([
            "status" => true,
            "message" => "hecho"
        ], 200);
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
