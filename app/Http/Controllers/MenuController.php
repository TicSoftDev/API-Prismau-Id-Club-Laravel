<?php

namespace App\Http\Controllers;

use App\Models\Menu;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class MenuController extends Controller
{

    public function crearMenu(Request $request)
    {
        DB::beginTransaction();
        try {
            $validated = $request->validate([
                'Name' => 'required|string',
                'Type' => 'required|string',
                'Icon' => 'required|string',
                'Route' => 'required|string',
                'Color' => 'required|string',
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'status' => false,
                'message' => 'Datos',
                'errors' => $e->errors(),
            ], 200);
        }
        try {
            $menu = Menu::create($validated);
            DB::commit();
            return response()->json([
                'status' => true,
                'message' => 'hecho',
                'data' => $menu
            ], 200);
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
                "message" => "Error en el servidor: " . $e->getMessage()
            ], 500);
        }
    }

    public function menus()
    {
        $menus = Menu::all();
        return response()->json($menus);
    }

    public function actualizarMenu(Request $request, string $id)
    {
        try {
            $validated = $request->validate([
                'Name' => 'required|string',
                'Type' => 'required|string',
                'Icon' => 'required|string',
                'Route' => 'required|string',
                'Color' => 'required|string',
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'status' => false,
                'message' => 'Datos',
                'errors' => $e->errors(),
            ], 200);
        }
        $menu = Menu::findOrFail($id);
        $menu->update($validated);
        return response()->json([
            "status" => true,
            "message" => "hecho",
            'menu' => $menu
        ], 201);
    }

    public function eliminarMenu(string $id)
    {
        $menu = Menu::findOrFail($id);
        $menu->delete();
        return response()->json([
            "status" => true,
            "message" => "hecho",
            'menu' => $menu
        ], 201);
    }
}
