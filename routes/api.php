<?php

use App\Http\Controllers\AdherenteController;
use App\Http\Controllers\AsociadoController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CargoController;
use App\Http\Controllers\EmpleadoController;
use App\Http\Controllers\EspacioController;
use App\Http\Controllers\EstadosController;
use App\Http\Controllers\FamiliarController;
use App\Http\Controllers\InvitadoController;
use App\Http\Controllers\PersonalController;
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

Route::group([
    "middleware" => ["auth:api"]
], function(){

    //usuario
    Route::put('usuario/{id}', [UsuarioController::class, 'update']);
    Route::get('usuario/{id}', [UsuarioController::class, 'show']);

    //personal
    Route::post('personal', [PersonalController::class, 'store']);
    Route::get('personal/familiares/{id}', [PersonalController::class, 'getPersonalWithFamiliares']);
    Route::put('personal/{id}', [PersonalController::class, 'update']);
    Route::post('personal/imagen/{id}', [PersonalController::class, 'changeImagen']);
    Route::delete('personal/{id}', [PersonalController::class, 'destroy']);

    // asociados
    Route::get('asociados', [AsociadoController::class, 'index']);
    Route::get('asociados/inactivos', [AsociadoController::class, 'show']);
    Route::get('asociados/cantidad', [AsociadoController::class, 'create']);
    Route::put('asociados/status/{id}', [AsociadoController::class, 'changeStatus']);

    // adherentes
    Route::get('adherentes', [AdherenteController::class, 'index']);
    Route::get('adherentes/inactivos', [AdherenteController::class, 'show']);
    Route::get('adherentes/cantidad', [AdherenteController::class, 'create']);
    Route::put('adherentes/status/{id}', [AdherenteController::class, 'changeStatus']);
    Route::put('adherentes/asociado/{id}', [AdherenteController::class, 'changeToAsociado']);

    //empleados
    Route::get('empleados', [EmpleadoController::class, 'index']);
    Route::post('empleados', [EmpleadoController::class, 'store']);
    Route::get('empleados/cantidad', [EmpleadoController::class, 'create']);
    Route::put('empleados/{id}', [EmpleadoController::class, 'update']);
    Route::post('empleados/imagen/{id}', [EmpleadoController::class, 'changeImagen']);
    Route::delete('empleados/{id}', [EmpleadoController::class, 'destroy']);

    //familiares
    Route::get('familiares/cantidad', [FamiliarController::class, 'create']);
    Route::get('familiares/{id}', [FamiliarController::class, 'index']);
    Route::post('familiares', [FamiliarController::class, 'store']);
    Route::post('familiares/imagen/{id}', [FamiliarController::class, 'changeImagen']);
    Route::put('familiares/{id}', [FamiliarController::class, 'update']);
    Route::delete('familiares/{id}', [FamiliarController::class, 'destroy']);

    //espacios
    Route::get('espacios/cantidad', [EspacioController::class, 'create']);
    Route::get('espacios', [EspacioController::class, 'index']);
    Route::post('espacios', [EspacioController::class, 'store']);
    Route::post('espacios/imagen/{id}', [EspacioController::class, 'changeImagen']);
    Route::put('espacios/{id}', [EspacioController::class, 'update']);
    Route::delete('espacios/{id}', [EspacioController::class, 'destroy']);

    //invitados
    Route::get('invitados', [InvitadoController::class, 'index']);
    Route::get('invitados/cantidad', [InvitadoController::class, 'create']);
    Route::post('invitados', [InvitadoController::class, 'store']);

    //estados
    Route::get('estados', [EstadosController::class, 'index']);


});