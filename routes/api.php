<?php

use App\Http\Controllers\AdherenteController;
use App\Http\Controllers\AdminsController;
use App\Http\Controllers\AsociadoController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ContratosController;
use App\Http\Controllers\EmpleadoController;
use App\Http\Controllers\EntradaController;
use App\Http\Controllers\EspacioController;
use App\Http\Controllers\EstadosController;
use App\Http\Controllers\FamiliarController;
use App\Http\Controllers\InvitadoController;
use App\Http\Controllers\NoticiaController;
use App\Http\Controllers\SolicitudesController;
use App\Http\Controllers\UsuarioController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::post("login", [AuthController::class, "login"]);
Route::post("contratos", [ContratosController::class, "crearSolicitudContratoApp"]);
Route::post('reset-password', [AuthController::class, 'sendResetCode']);
Route::post('verify-reset-code', [AuthController::class, 'validateResetCode']);
Route::post('change-password', [AuthController::class, 'resetPassword']);

Route::group([
    "middleware" => ["auth:api"]
], function () {

    //contratos app
    Route::get('contratos', [ContratosController::class, 'contratosApp']);
    Route::get('contratos/cantidad', [ContratosController::class, 'contContratosApp']);

    //admin 
    Route::post('admin', [AdminsController::class, 'crearAdmin']);
    Route::get('admin', [AdminsController::class, 'admins']);
    Route::get('admin/cantidad', [AdminsController::class, 'contAdmins']);
    Route::put('admin/{id}', [AdminsController::class, 'actualizarAdmin']);
    Route::put('admin/status/{id}', [AdminsController::class, 'changeStatus']);
    Route::delete('admin/{id}', [AdminsController::class, 'eliminarAdmin']);

    //usuario
    Route::get('usuario', [UsuarioController::class, 'filtroUsuarios']);
    Route::get('usuario/{id}', [UsuarioController::class, 'buscarUsuario']);
    Route::put('usuario/{id}', [UsuarioController::class, 'cambiarPassword']);

    // asociados
    Route::post('asociados', [AsociadoController::class, 'crearAsociado']);
    Route::get('asociados', [AsociadoController::class, 'asociados']);
    Route::get('asociados/cantidad', [AsociadoController::class, 'cantidadAsociados']);
    Route::get('asociados/familiares/{id}', [AsociadoController::class, 'asociadoConFamiliares']);
    Route::put('asociados/{id}', [AsociadoController::class, 'actualizarAsociado']);
    Route::put('asociados/status/{id}', [AsociadoController::class, 'changeStatus']);
    Route::post('asociados/imagen/{id}', [AsociadoController::class, 'changeImagen']);
    Route::delete('asociados/{id}', [AsociadoController::class, 'eliminarAsociado']);

    // adherentes
    Route::post('adherentes', [AdherenteController::class, 'crearAdherente']);
    Route::get('adherentes', [AdherenteController::class, 'adherentes']);
    Route::get('adherentes/cantidad', [AdherenteController::class, 'contAdherentes']);
    Route::get('adherentes/familiares/{id}', [AdherenteController::class, 'adherenteConFamiliares']);
    Route::put('adherentes/{id}', [AdherenteController::class, 'actualizarAdherente']);
    Route::put('adherentes/asociado/{id}', [AdherenteController::class, 'changeToAsociado']);
    Route::put('adherentes/status/{id}', [AdherenteController::class, 'changeStatus']);
    Route::post('adherentes/imagen/{id}', [AdherenteController::class, 'changeImagen']);
    Route::delete('adherentes/{id}', [AdherenteController::class, 'eliminarAdherente']);

    //empleados
    Route::post('empleados', [EmpleadoController::class, 'crearEmpleado']);
    Route::get('empleados', [EmpleadoController::class, 'empleados']);
    Route::get('empleados/cantidad', [EmpleadoController::class, 'cantidadEmpleados']);
    Route::put('empleados/{id}', [EmpleadoController::class, 'actualizarEmpleado']);
    Route::post('empleados/imagen/{id}', [EmpleadoController::class, 'changeImagen']);
    Route::delete('empleados/{id}', [EmpleadoController::class, 'eliminarEmpleado']);

    //familiares
    Route::post('familiares/asociado', [FamiliarController::class, 'crearFamiliaresAsociado']);
    Route::post('familiares/adherente', [FamiliarController::class, 'crearFamiliaresAdherente']);
    Route::post('familiares/imagen/{id}', [FamiliarController::class, 'changeImagen']);
    Route::get('familiares/cantidad', [FamiliarController::class, 'contFamiliares']);
    Route::get('familiares/cantidad/{id}/{rol}', [FamiliarController::class, 'contFamiliaresAsociado']);
    Route::get('familiares/{id}/{rol}', [FamiliarController::class, 'familiaresAsociado']);
    Route::put('familiares/{id}', [FamiliarController::class, 'actualizarFamiliar']);
    Route::delete('familiares/{id}', [FamiliarController::class, 'eliminarFamiliar']);

    //espacios
    Route::post('espacios', [EspacioController::class, 'crearEspacio']);
    Route::post('espacios/imagen/{id}', [EspacioController::class, 'changeImagen']);
    Route::get('espacios/cantidad', [EspacioController::class, 'contEspacios']);
    Route::get('espacios', [EspacioController::class, 'espacios']);
    Route::put('espacios/{id}', [EspacioController::class, 'actualizarEspacio']);
    Route::delete('espacios/{id}', [EspacioController::class, 'eliminarEspacio']);

    //invitados
    Route::post('invitados', [InvitadoController::class, 'crearInvitacion']);
    Route::get('invitados', [InvitadoController::class, 'invitados']);
    Route::get('invitados/cantidad', [InvitadoController::class, 'contInvitadosMes']);
    Route::get('invitados/cantidad/{id}', [InvitadoController::class, 'contInvitadosUser']);
    Route::put('invitados/{id}', [InvitadoController::class, 'update']);

    //estados
    Route::get('estados', [EstadosController::class, 'estados']);

    //entradas
    Route::get('entradas', [EntradaController::class, 'entradas']);
    Route::post('entradas/{id}', [EntradaController::class, 'crearEntrada']);

    //noticias
    Route::post('noticias', [NoticiaController::class, 'crearNoticia']);
    Route::get('noticias', [NoticiaController::class, 'noticias']);
    Route::get('noticias/cantidad', [NoticiaController::class, 'Contnoticias']);
    Route::put('noticias/{id}', [NoticiaController::class, 'actualizarNoticia']);
    Route::delete('noticias/{id}', [NoticiaController::class, 'eliminarNoticia']);

    //solicitudes
    Route::post('solicitudes', [SolicitudesController::class, 'crearSolicitud']);
    Route::get('solicitudes', [SolicitudesController::class, 'solicitudes']);
    Route::get('solicitudes/{id}', [SolicitudesController::class, 'solicitud']);
    Route::get('solicitudes/user/{id}', [SolicitudesController::class, 'getSolicitudUser']);
    Route::get('solicitudes/cantidad', [SolicitudesController::class, 'contSolicitudes']);
});
