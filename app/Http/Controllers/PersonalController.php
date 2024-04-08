<?php

namespace App\Http\Controllers;

use App\Http\Requests\PersonalRequest;
use App\Models\Personal;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class PersonalController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $users = personal::all();
        return response()->json($users);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(PersonalRequest $request)
    {
        $user = User::create([
            'Documento' => $request->Documento,
            'password' => Hash::make($request->Documento),
            'Rol' => $request->Rol
        ]);

        $personal = new personal();
        $personal->user_id = $user->id;
        $personal->asociado_id = $request->asociado_id;
        $personal->Nombre = $request->Nombre;
        $personal->Apellidos = $request->Apellidos;
        $personal->Correo = $request->Correo;
        $personal->Telefono = $request->Telefono;
        $personal->FechaNacimiento = $request->FechaNacimiento;
        $personal->LugarNacimiento = $request->LugarNacimiento;
        $personal->TipoDocumento = $request->TipoDocumento;
        $personal->Documento = $request->Documento;
        $personal->Sexo = $request->Sexo;
        $personal->DireccionResidencia = $request->DireccionResidencia;
        $personal->CiudadResidencia = $request->CiudadResidencia;
        $personal->TiempoResidencia = $request->TiempoResidencia;
        $personal->EstadoCivil = $request->EstadoCivil;
        $personal->Profesion = $request->Profesion;
        $personal->Trabajo = $request->Trabajo;
        $personal->Cargo = $request->Cargo;
        $personal->TiempoServicio = $request->TiempoServicio;
        $personal->TelOficina = $request->TelOficina;
        $personal->DireccionOficina = $request->DireccionOficina;
        $personal->CiudadOficina = $request->CiudadOficina;
        $personal->Estado = $request->Estado;
        $rest = $personal->save();

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


    /**
     * Display the specified resource.
     */
    public function show(personal $personal)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(personal $personal)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(PersonalRequest $request, string $id)
    {
        $usuario = User::find($id);
        $usuario->update([
            'Documento' => $request->Documento,
        ]);
        $res = $usuario->personal->update([
            "asociado_id" => $request->asociado_id,
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
            "TiempoResidencia" => $request->TiempoResidencia,
            "EstadoCivil" => $request->EstadoCivil,
            "Profesion" => $request->Profesion,
            "Trabajo" => $request->Trabajo,
            "Cargo" => $request->Cargo,
            "TiempoServicio" => $request->TiempoServicio,
            "TelOficina" => $request->TelOficina,
            "DireccionOficina" => $request->DireccionOficina,
            "CiudadOficina" => $request->CiudadOficina,
            "Estado" => $request->Estado,
        ]);

        if ($res > 0) {
            return response()->json([
                "message" => "hecho"
            ], 201);
        } else {
            response()->json([
                "message" => "No se pudo agregar"
            ],);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $user = User::find($id);
        if (is_null($user)) {
            return response()->json(["message" => "no encontrado"], 404);
        }
        if ($user->personal->imagen) {
            Storage::disk('public')->delete(str_replace('/storage', 'public', $user->personal->imagen));
        }
        if ($user->personal->familiares) {
            foreach ($user->personal->familiares as $familiar) {
                if ($familiar->imagen) {
                    Storage::disk('public')->delete(str_replace('/storage', 'public', $familiar->imagen));
                }
                $usuarioFamiliar = User::find($familiar->user_id);
                $usuarioFamiliar->delete();
                $familiar->delete();
            }
        }
        $user->personal->delete();
        $user->delete();
        return response()->json(["message" => "hecho"], 200);
    }

    public function changeImagen(Request $request, $id)
    {
        $persona = Personal::find($id);
        $imagen = $request->file('imagen');
        $nameImage = Str::slug($persona->Documento) . '_' . time() . '.' . $imagen->getClientOriginalExtension();
        $imagen = $imagen->storeAs('public/personal', $nameImage);
        $url = Storage::url($imagen);

        if ($persona->imagen) {
            Storage::disk('public')->delete(str_replace('/storage', 'public', $persona->imagen));
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

    public function getPersonalWithFamiliares($id)
    {
        $personal = Personal::with('familiares')->find($id);
        return response()->json($personal);
    }
}
