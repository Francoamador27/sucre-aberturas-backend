<?php

use App\Http\Controllers\Api\ConsulController;
use App\Http\Controllers\Api\MailConfigController;
use App\Http\Controllers\Api\PdfController;
use App\Http\Controllers\Api\ContactoController;
// use App\Http\Controllers\Api\CuponController;
// use App\Http\Controllers\Api\CartDiscountController;
use App\Http\Controllers\Api\TestimonioController;
use App\Http\Controllers\AuthController;
// use App\Http\Controllers\CarritoController;
// use App\Http\Controllers\CategoriaController;
// use App\Http\Controllers\EjemploController;
use App\Http\Controllers\CategoriaGastoController;
use App\Http\Controllers\DoctorController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\EjemploController;
use App\Http\Controllers\EmailController;
// use App\Http\Controllers\PedidoController;
// use App\Http\Controllers\ProductoController;
use App\Http\Controllers\EstadisticasController;
use App\Http\Controllers\GastoController;
use App\Http\Controllers\OdontogramaController;
use App\Http\Controllers\PatientController;
use App\Http\Controllers\PatologiaController;
use App\Http\Controllers\ServicioController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\UserController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
// use App\Http\Controllers\MercadoPagoController;
use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\Auth\ResetPasswordController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/
use App\Http\Controllers\Api\EventController;

Route::get('/patients/{id}/events', [EventController::class, 'byPatient']);
Route::get('/events', [EventController::class, 'index']);          // sin paginar

Route::middleware('auth:sanctum')->group(function () {
    // --- USUARIOS (OK) ---
    Route::get('/user', function (Request $request) {
        return $request->user();
    });
    Route::get('/user/documents', [DocumentController::class, 'myDocuments']);
    Route::post('/user/documents', [DocumentController::class, 'myUpload']);

    Route::put('/user/update', [UserController::class, 'actualizarPerfil']);
    Route::post('/user/password', [UserController::class, 'cambiarPassword']);
    Route::get('/user/events', [EventController::class, 'myEvents']);

    Route::get('/usuarios', [UserController::class, 'index']);
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/pacientes/{patient}', [PatientController::class, 'show']);
    Route::get('/pacientes/id/{patient}', [PatientController::class, 'showByUser']);


    Route::post('/documentos', [DocumentController::class, 'store']);
    Route::get('/documentos', [DocumentController::class, 'index']);
    // --- E-COMMERCE (COMENTADO) ---
    // Route::post('/generar-pdf', [PdfController::class, 'generar']);
    // Route::put('/pedidos/{pedido}', [PedidoController::class, 'update']);
    // Route::put('productos/{producto}', [ProductoController::class, 'update']);
    // Route::delete('/pedidos/{pedido}', [PedidoController::class, 'destroy']);
    // Route::get('/pedidos/{pedido}', [PedidoController::class, 'show']);
    // Route::get('/mis-pedidos', [PedidoController::class, 'misPedidos']);
});

// --- EMAILS (OK) ---
Route::post('/contacto', [ContactoController::class, 'enviar'])->middleware('turnstile');
Route::get('/probar-envio-email', [EmailController::class, 'probarEnvio']);

// --- AUTH (OK) ---
Route::post('/reset-password', [ResetPasswordController::class, 'reset']);
Route::post('/forgot-password', [ForgotPasswordController::class, 'sendResetLink'])->middleware('turnstile');
Route::post('/register', [AuthController::class, 'register'])->middleware('turnstile');
Route::post('/login', [AuthController::class, 'login'])->middleware('turnstile');

// --- E-COMMERCE PÚBLICO (COMENTADO) ---
// Route::apiResource('/categorias', CategoriaController::class);
// Route::get('productos/{producto}', [ProductoController::class, 'show']);
// Route::get('productos', [ProductoController::class, 'index']);
// Route::post('/pedidos', [PedidoController::class, 'store']);
// Route::apiResource('/carritos', CarritoController::class);
// Route::post('/mercadopago/webhook', [MercadoPagoController::class, 'webhook']);
// Route::get('/coupons/validate', [CuponController::class, 'validar']);
// Route::get('/coupons', [CuponController::class, 'index']);
// Route::get('/cart-discounts', [CartDiscountController::class, 'index']);
Route::get('/testimonios', [TestimonioController::class, 'index']);
Route::get('/ejemplos', [EjemploController::class, 'index']);
Route::get('/servicios', [ServicioController::class, 'index']);
Route::get('/servicios/{servicio}', [ServicioController::class, 'show']);

Route::get('/settings', [SettingController::class, 'index']);
// --- ADMIN (SOLO USUARIOS OK, RESTO COMENTADO) ---
Route::middleware(['auth:sanctum', 'IsAdmin'])->group(function () {
    Route::post('/patologias', [PatologiaController::class, 'store']);
    Route::get('/patologias/paciente/{idpa}', [PatologiaController::class, 'getByPatient']);
    Route::get('/patologias/{id}', [PatologiaController::class, 'show']);
    Route::put('/patologias/{id}', [PatologiaController::class, 'update']);
    Route::delete('/patologias/{id}', [PatologiaController::class, 'destroy']);

    Route::post('/settings', [SettingController::class, 'update']);
    Route::get('/mail-config', [MailConfigController::class, 'show']);
    Route::put('/mail-config', [MailConfigController::class, 'update']);


    Route::get('/pacientes', [PatientController::class, 'index']);


    Route::get('/usuarios', [UserController::class, 'index']);
    Route::get('/usuarios/{user}', [UserController::class, 'show']);
    Route::put('/usuarios/{user}', [UserController::class, 'update']);
    Route::delete('/usuarios/{user}', [UserController::class, 'destroy']);


    Route::post('/events', [EventController::class, 'store']);
    Route::delete('/events/{event}', [EventController::class, 'destroy']);
    Route::put('/events/{event}', [EventController::class, 'update']);
    Route::get('/events/{event}', [EventController::class, 'show']);


    Route::apiResource('doctores', DoctorController::class)
        ->parameters(['doctores' => 'doctor']); // ahora es /doctores/{doctor}

    Route::get('/pacientes', [PatientController::class, 'index']);
    Route::post('/pacientes', [PatientController::class, 'store']);
    Route::put('/pacientes/{paciente}', [PatientController::class, 'update']);
    Route::delete('/pacientes/{paciente}', [PatientController::class, 'destroy']);

    Route::get('/odontograma/{idpa}', [OdontogramaController::class, 'show']);
    Route::post('/odontograma/{idpa}', [OdontogramaController::class, 'store']); // único guardar


    Route::get('/consul/{idpa}', [ConsulController::class, 'index']);
    Route::post('/consul/{idpa}', [ConsulController::class, 'store']);
    Route::delete('/consul/{idconslt}', [ConsulController::class, 'destroy']);


    Route::post('/testimonios', [TestimonioController::class, 'store']);
    Route::delete('/testimonios/{id}', [TestimonioController::class, 'destroy']);

    Route::post('/ejemplos', [EjemploController::class, 'store']);
    Route::delete('/ejemplos/{id}', [EjemploController::class, 'destroy']);


    Route::post('/servicios', [ServicioController::class, 'store']);
    Route::delete('/servicios/{servicio}', [ServicioController::class, 'destroy']);


    Route::delete('documentos/{document}', [DocumentController::class, 'destroy']);


    Route::get('/gastos', [GastoController::class, 'index']);
    Route::get('/gastos/{id}', [GastoController::class, 'show']);
    Route::post('/gastos', [GastoController::class, 'store']);
    Route::put('/gastos/{id}', [GastoController::class, 'update']);
    Route::delete('/gastos/{id}', [GastoController::class, 'destroy']);

    // Rutas adicionales de gastos
    Route::get('/gastos/categoria/{idcat}', [GastoController::class, 'getByCategoria']);
    Route::post('/gastos/buscar-por-fechas', [GastoController::class, 'getByFechas']);

    // ========== RUTAS PARA CATEGORÍAS DE GASTOS ==========
    Route::get('/categorias-gastos', [CategoriaGastoController::class, 'index']);
    Route::get('/categorias-gastos/{id}', [CategoriaGastoController::class, 'show']);
    Route::post('/categorias-gastos', [CategoriaGastoController::class, 'store']);
    Route::put('/categorias-gastos/{id}', [CategoriaGastoController::class, 'update']);
    Route::delete('/categorias-gastos/{id}', [CategoriaGastoController::class, 'destroy']);

    // Rutas adicionales de categorías
    Route::get('/categorias-gastos/{id}/con-gastos', [CategoriaGastoController::class, 'getWithGastos']);
    Route::get('/categorias-gastos/{id}/estadisticas', [CategoriaGastoController::class, 'getEstadisticas']);


    Route::get('/estadisticas', [EstadisticasController::class, 'index']);

    // E-commerce admin (COMENTADO)
    // Route::post('/productos', [ProductoController::class, 'store']);
    // Route::delete('/productos/{producto}', [ProductoController::class, 'destroy']);

    // Route::post('/cart-discounts', [CartDiscountController::class, 'store']);
    // Route::get('/cart-discounts/{id}', [CartDiscountController::class, 'show']);
    // Route::put('/cart-discounts/{id}', [CartDiscountController::class, 'update']);
    // Route::delete('/cart-discounts/{id}', [CartDiscountController::class, 'destroy']);

    // Route::post('/coupons', [CuponController::class, 'store']);
    // Route::delete('/coupons/{id}', [CuponController::class, 'destroy']);
});
