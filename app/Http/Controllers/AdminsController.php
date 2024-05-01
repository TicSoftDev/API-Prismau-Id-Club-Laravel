<?php

namespace App\Http\Controllers;

use App\Http\Requests\PersonalRequest;
use App\Models\Admin;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class AdminsController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $admins = User::with('admin')->where('Rol', 1)->get();
        return response()->json($admins);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $admins = User::with('admin')->where('Rol', 1)->count();
        return response()->json($admins);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(PersonalRequest $request)
    {
        DB::beginTransaction();
        try {
            $user = User::create([
                'Documento' => $request->Documento,
                'password' => Hash::make($request->Clave),
                'Rol' => $request->Rol
            ]);

            $admin = new Admin();
            $admin->user_id = $user->id;
            $admin->Nombre = $request->Nombre;
            $admin->Apellidos = $request->Apellidos;
            $admin->Correo = $request->Correo;
            $admin->Telefono = $request->Telefono;
            $admin->Estado = 1;
            $admin->save();

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
     * Display the specified resource.
     */
    public function show(Admin $admins)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Admin $admins)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        DB::beginTransaction(); 
        try {
            $usuario = User::findOrFail($id);
            $usuario->admin->update([
                "Nombre" => $request->Nombre,
                "Apellidos" => $request->Apellidos,
                "Correo" => $request->Correo,
                "Telefono" => $request->Telefono,
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
    public function destroy(string $id)
    {
        $user = User::find($id);
        if (is_null($user)) {
            return response()->json([
                "status" => false,
                "message" => "no encontrado"
            ], 404);
        }
        $user->admin->delete();
        $user->delete();
        return response()->json([
            "status" => true,
            "message" => "hecho"
        ], 200);
    }
}
