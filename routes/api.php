<?php

use App\Http\Controllers\AdherenteController;
use App\Http\Controllers\AdminsController;
use App\Http\Controllers\AsociadoController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ContratosController;
use App\Http\Controllers\CuotasBaileController;
use App\Http\Controllers\DisponibilidadEspacioController;
use App\Http\Controllers\EmpleadoController;
use App\Http\Controllers\EncuestasController;
use App\Http\Controllers\EntradaController;
use App\Http\Controllers\EspacioController;
use App\Http\Controllers\EstadosController;
use App\Http\Controllers\FamiliarController;
use App\Http\Controllers\InvitadoController;
use App\Http\Controllers\MensualidadesController;
use App\Http\Controllers\MenuController;
use App\Http\Controllers\MenuRoleController;
use App\Http\Controllers\NoticiaController;
use App\Http\Controllers\PagosController;
use App\Http\Controllers\PagosCuotasBaileController;
use App\Http\Controllers\PreguntasController;
use App\Http\Controllers\ReservasController;
use App\Http\Controllers\RespuestasController;
use App\Http\Controllers\RespuestasUsuarioController;
use App\Http\Controllers\RubrosController;
use App\Http\Controllers\SolicitudesController;
use App\Http\Controllers\UsuarioController;
use App\Models\CuotasBaile;
use App\Models\Pagos;
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
Route::post('preferencia-mensualidad', [MensualidadesController::class, 'crearPreferencia']);
Route::post('preferencia-cuota-baile', [CuotasBaileController::class, 'crearPreferencia']);
Route::post('webhook', [PagosController::class, 'handleWebhook']);

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
    Route::get('usuario/socios', [UsuarioController::class, 'consultarSociosConValores']);
    Route::get('usuario/{id}', [UsuarioController::class, 'buscarUsuario']);
    Route::get('usuario', [UsuarioController::class, 'filtroUsuarios']);
    Route::put('usuario/{id}', [UsuarioController::class, 'cambiarPassword']);
    Route::put('usuario/reset-password/{id}', [UsuarioController::class, 'resetearPassword']);
    Route::delete('usuario/{id}', [UsuarioController::class, 'eliminarCuenta']);

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
    Route::get('invitados/entradas', [InvitadoController::class, 'entradasInvitados']);
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
    Route::get('solicitudes/pendientes', [SolicitudesController::class, 'contSolicitudesPendientes']);
    Route::get('solicitudes/cantidad/{id}', [SolicitudesController::class, 'contSolicitudesUser']);
    Route::get('solicitudes/{id}', [SolicitudesController::class, 'solicitud']);
    Route::get('solicitudes/user/{id}', [SolicitudesController::class, 'getSolicitudUser']);
    Route::put('solicitudes/{id}', [SolicitudesController::class, 'responderSolicitud']);

    //Disponibilidades de Espacios
    Route::post('disponibilidad-espacio', [DisponibilidadEspacioController::class, 'crearDisponibilidad']);
    Route::get('disponibilidad-espacio/{id}', [DisponibilidadEspacioController::class, 'getDisponibilidadesEspacio']);
    Route::put('disponibilidad-espacio/{id}', [DisponibilidadEspacioController::class, 'updateDisponibilidad']);
    Route::delete('disponibilidad-espacio/{id}', [DisponibilidadEspacioController::class, 'eliminarDisponibilidad']);

    //Reservaciones
    Route::post('reservas', [ReservasController::class, 'crearReservacion']);
    Route::get('reservas', [ReservasController::class, 'reservas']);
    Route::get('reservas/cantidad', [ReservasController::class, 'contReservas']);
    Route::get('reservas/cantidad/{id}', [ReservasController::class, 'contReservasUser']);
    Route::get('reservas/{id}', [ReservasController::class, 'getReservasUser']);
    Route::delete('reservas/{id}', [ReservasController::class, 'cancelarReserva']);

    //Encuestas
    Route::post('encuestas', [EncuestasController::class, 'crearEncuesta']);
    Route::get('encuestas', [EncuestasController::class, 'encuestas']);
    Route::get('encuestas/cantidad', [EncuestasController::class, 'contEncuestas']);
    Route::get('encuestas/disponibles/{id}', [EncuestasController::class, 'encuestasDisponibles']);
    Route::get('encuestas/respuestas/{id}', [EncuestasController::class, 'getEncuestaConRespuestas']);
    Route::get('encuestas/{id}', [EncuestasController::class, 'getEncuesta']);
    Route::put('encuestas/{id}', [EncuestasController::class, 'actualizarEncuesta']);
    Route::delete('encuestas/{id}', [EncuestasController::class, 'borrarEncuesta']);

    //Preguntas
    Route::post('preguntas', [PreguntasController::class, 'crearPregunta']);
    Route::get('preguntas/encuesta/{id}', [PreguntasController::class, 'preguntas']);
    Route::get('preguntas/cantidad/{id}', [PreguntasController::class, 'contPreguntas']);
    Route::get('preguntas/{id}', [PreguntasController::class, 'getPregunta']);
    Route::put('preguntas/{id}', [PreguntasController::class, 'actualizarPregunta']);
    Route::delete('preguntas/{id}', [PreguntasController::class, 'borrarPregunta']);

    //Respuestas
    Route::post('respuestas', [RespuestasController::class, 'crearRespuesta']);
    Route::get('respuestas/{id}', [RespuestasController::class, 'respuestas']);
    Route::put('respuestas/{id}', [RespuestasController::class, 'actualizarRespuesta']);
    Route::delete('respuestas/{id}', [RespuestasController::class, 'borrarRespuesta']);

    Route::post('/respuestas-usuarios', [RespuestasUsuarioController::class, 'guardarRespuestasUsuarios']);

    //Menu
    Route::post('menus', [MenuController::class, 'crearMenu']);
    Route::get('menus', [MenuController::class, 'menus']);
    Route::put('menus/{id}', [MenuController::class, 'actualizarMenu']);
    Route::delete('menus/{id}', [MenuController::class, 'eliminarMenu']);

    Route::post('menus/rol', [MenuRoleController::class, 'asignarMenuRol']);
    Route::get('menus/rol/{id}', [MenuRoleController::class, 'menusRole']);
    Route::get('menus/rol/{id}/portal', [MenuRoleController::class, 'menusRolePortal']);
    Route::get('menus/rol/{id}/bienestar', [MenuRoleController::class, 'menusRoleBienestar']);
    Route::get('menus/rol/{id}/pagos', [MenuRoleController::class, 'menusRolePagos']);
    Route::get('menus/rol/{id}/perfil', [MenuRoleController::class, 'menusRolePerfil']);
    Route::delete('menus/rol/{menuId}/{rolId}', [MenuRoleController::class, 'eliminarMenuDeRol']);

    //Rubros
    Route::post('rubros', [RubrosController::class, 'crearRubro']);
    Route::get('rubros', [RubrosController::class, 'rubros']);
    Route::put('rubros/{id}', [RubrosController::class, 'actualizarRubro']);
    Route::delete('rubros/{id}', [RubrosController::class, 'borrarRubro']);

    //Pagos Mensualidad
    Route::post('pagos/facturas', [PagosController::class, 'generarFacturas']);
    Route::get('pagos', [PagosController::class, 'getPagos']);

    //Mensualidades
    Route::post('mensualidades', [MensualidadesController::class, 'pagarMensualidad']);
    Route::get('mensualidades/{documento}', [MensualidadesController::class, 'consultarMensualidadesUser']);
    Route::put('mensualidades/valor', [MensualidadesController::class, 'cambiarValorMensualidadUser']);

    //Cuotas Baile
    Route::post('cuotas-baile', [CuotasBaileController::class, 'pagarCuota']);
    Route::get('cuotas-baile/{documento}', [CuotasBaileController::class, 'consultarCuotasBaileUser']);
    Route::put('cuotas-baile/valor', [CuotasBaileController::class, 'cambiarValorCuotasBaileUser']);

    //Pagos Cuota Baile
    Route::get('pagos-cuotas-baile', [PagosCuotasBaileController::class, 'getPagos']);
});
