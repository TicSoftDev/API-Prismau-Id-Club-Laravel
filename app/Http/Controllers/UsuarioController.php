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
    public function show(User $user)
    {
        //
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
    public function update(Request $request, String $id)
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
