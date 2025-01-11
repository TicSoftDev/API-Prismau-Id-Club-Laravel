<?php

namespace App\Http\Controllers;

use App\Models\Admin;
use App\Models\User;
use App\services\AdminService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class AdminsController extends Controller
{

    protected $adminService;

    public function __construct(AdminService $adminService)
    {
        $this->adminService = $adminService;
    }

    public function crearAdmin(Request $request)
    {
        return $this->adminService->crearAdmin($request);
    }

    public function admins()
    {
        return $this->adminService->getAdmins();
    }

    public function contAdmins()
    {
        $admins = User::with('admin')->where('Rol', 1)->count();
        return response()->json($admins);
    }

    public function actualizarAdmin(Request $request, string $id)
    {
        return $this->adminService->actualizarAdmin($request, $id);
    }

    public function changeStatus(String $id)
    {
        return $this->adminService->changeStatus($id);
    }

    public function eliminarAdmin(string $id)
    {
        return $this->adminService->eliminarAdmin($id);
    }
}
