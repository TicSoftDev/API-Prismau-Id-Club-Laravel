<?php

namespace App\Http\Controllers;

use App\Models\Menu_Role;
use App\Models\Rol;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class MenuRoleController extends Controller
{

    public function asignarMenuRol(Request $request)
    {
        try {
            $validated =  $request->validate([
                'menu_id' => 'required|exists:menus,id',
                'role_id' => 'required|exists:roles,id',
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'status' => false,
                'message' => 'Datos',
                'errors' => $e->errors(),
            ], 200);
        }
        $menu_role = Menu_Role::create($validated);
        return response()->json([
            "status" => true,
            "message" => "hecho",
            "data" => $menu_role
        ]);
    }

    public function menusRole($id)
    {
        $role = Rol::findOrFail($id);
        $menus = $role->menus;
        return response()->json($menus);
    }
    
    public function menusRolePortal($id)
    {
        $rol = Rol::findOrFail($id);
        $menus = $rol->menus()->where('type', 'portal')->get();
        return response()->json($menus);
    }

    public function menusRoleBienestar($id)
    {
        $rol = Rol::findOrFail($id);
        $menus = $rol->menus()->where('type', 'bienestar')->get();
        return response()->json($menus);
    }
    
    public function menusRolePagos($id)
    {
        $rol = Rol::findOrFail($id);
        $menus = $rol->menus()->where('type', 'pagos')->get();
        return response()->json($menus);
    }

    public function menusRolePerfil($id)
    {
        $rol = Rol::findOrFail($id);
        $menus = $rol->menus()->where('type', 'perfil')->get();
        return response()->json($menus);
    }

    public function eliminarMenuDeRol($menuId, $rolId)
    {
        $role = Rol::findOrFail($rolId);
        if (!$role) {
            return response()->json([
                'status' => false,
                'message' => 'Rol no encontrado',
            ], 404);
        }
        if ($role->menus()->where('menu_id', $menuId)->exists()) {
            $role->menus()->detach($menuId);
            return response()->json([
                'status' => true,
                'message' => 'Menú eliminado del rol correctamente',
            ], 200);
        } else {
            return response()->json([
                'status' => false,
                'message' => 'Relación entre el menú y el rol no encontrada',
            ], 404);
        }
    }
}
