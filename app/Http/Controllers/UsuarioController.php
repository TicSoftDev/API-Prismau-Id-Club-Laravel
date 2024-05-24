<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UsuarioController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $users = User::all();
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
    public function store(Request $request)
    {
        $user = User::create([
            'Nombre' => $request->Nombre,
            'Apellidos' => $request->Apellidos,
            'Fecha' => $request->Fecha,
            'Edad' => $request->Edad,
            'Documento' => $request->Documento,
            'Sexo' => $request->Sexo,
            'Telefono' => $request->Telefono,
            'Direccion' => $request->Direccion,
            'Correo' => $request->Correo,
            'password' => Hash::make($request->password),
        ]);
        return response()->json([
            "user" => $user
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function buscarUsuario(String $documento)
    {
        $user = User::where('Documento', $documento)->first();
        if ($user) {
            if ($user->Rol == 0 || $user->Rol == 1) {
                $usuario =  $user->admin;
            } else if ($user->Rol == 2) {
                $usuario =  $user->asociado;
            } else if ($user->Rol == 3) {
                $usuario =  $user->adherente;
            } else if ($user->Rol == 4 || $user->Rol == 6) {
                $usuario =  $user->empleado;
            } else if ($user->Rol == 5) {
                $usuario =  $user->familiar;
            }
            return response()->json([
                "status" => true,
                "user" => $usuario,
                "credenciales" => $user,
            ]);
        } else {
            return response()->json([
                "status" => false,
            ]);
        }
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(User $user)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function cambiarPassword(Request $request, String $id)
    {
        $usuario = User::find($id);
        $usuario->password = Hash::make($request->password);
        $usuario->save();

        return response()->json([
            "message" => "hecho"
        ], 201);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(User $user)
    {
        //
    }
}
