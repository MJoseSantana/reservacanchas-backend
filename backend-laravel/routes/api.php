<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CanchaController;
use App\Http\Controllers\ReservaController;
use App\Http\Controllers\UsuarioController;
use App\Http\Controllers\SancionController;
use App\Http\Controllers\FavoritoController;
use App\Http\Controllers\CalificacionController;
use App\Http\Controllers\ReporteController;
use App\Http\Controllers\ReporteJugadorController;

// Health check
Route::get('/health', function () {
    return response()->json([
        'success' => true,
        'message' => 'API funcionando correctamente',
        'timestamp' => now()->toIso8601String()
    ]);
});

// Rutas públicas (sin autenticación)
Route::prefix('auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
});

// Canchas públicas
Route::get('/canchas', [CanchaController::class, 'index']);
Route::get('/canchas/buscar', [CanchaController::class, 'search']);
Route::post('/canchas/registro', [CanchaController::class, 'storeFromRegistration']); // Crear cancha durante registro
// NOTA: Route::get('/canchas/{id}') movida al final después de rutas protegidas

// Rutas protegidas con Sanctum
Route::middleware('auth:sanctum')->group(function () {
    
    // Auth
    Route::prefix('auth')->group(function () {
        Route::get('/profile', [AuthController::class, 'profile']);
        Route::put('/profile', [AuthController::class, 'updateProfile']);
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::post('/verify-token', [AuthController::class, 'verifyToken']);
    });
    
    // Endpoint de prueba para debug
    Route::get('/debug/user-info', function(\Illuminate\Http\Request $request) {
        $user = $request->user();
        return response()->json([
            'success' => true,
            'user' => [
                'id' => $user->id,
                'email' => $user->email,
                'nombre' => $user->nombre,
                'rol' => $user->rol,
                'estado' => $user->estado,
            ],
            'canchas_count' => \App\Models\Cancha::where('dueno_id', $user->id)->count(),
        ]);
    });

    // Canchas protegidas
    Route::prefix('canchas')->group(function () {
        Route::get('/mis-canchas', [CanchaController::class, 'misCanchas']);
        Route::post('/', [CanchaController::class, 'store']);
        Route::put('/{id}', [CanchaController::class, 'update']);
        Route::delete('/{id}', [CanchaController::class, 'destroy']);
        Route::post('/{id}/imagenes', [CanchaController::class, 'uploadImage']);
    });

    // Reservas
    Route::prefix('reservas')->group(function () {
        Route::get('/mis-reservas', [ReservaController::class, 'misReservas']); // ANTES de la ruta dinámica
        Route::get('/', [ReservaController::class, 'index']);
        Route::post('/', [ReservaController::class, 'store']);
        Route::post('/verificar-disponibilidad', [ReservaController::class, 'verificarDisponibilidadPublic']);
        Route::put('/{id}/cancelar', [ReservaController::class, 'cancelar']);
        Route::get('/{id}', [ReservaController::class, 'show']); // Ruta dinámica al final
    });

    // Reservas por cancha (para dueños)
    Route::get('/canchas/{canchaId}/reservas', [ReservaController::class, 'reservasPorCancha']);

    // Favoritos
    Route::prefix('favoritos')->group(function () {
        Route::get('/', [FavoritoController::class, 'index']);
        Route::post('/', [FavoritoController::class, 'store']);
        Route::delete('/{id}', [FavoritoController::class, 'destroy']);
        Route::get('/check/{canchaId}', [FavoritoController::class, 'check']);
        Route::post('/toggle', [FavoritoController::class, 'toggle']);
    });

    // Calificaciones
    Route::prefix('calificaciones')->group(function () {
        Route::get('/', [CalificacionController::class, 'index']);
        Route::post('/', [CalificacionController::class, 'store']);
        Route::get('/{id}', [CalificacionController::class, 'show']);
        Route::put('/{id}', [CalificacionController::class, 'update']);
        Route::delete('/{id}', [CalificacionController::class, 'destroy']);
    });

    // Calificaciones por cancha
    Route::get('/canchas/{canchaId}/calificaciones', [CalificacionController::class, 'porCancha']);

    // Reportes de Canchas
    Route::prefix('reportes')->group(function () {
        Route::get('/', [ReporteController::class, 'index']); // Admin
        Route::get('/mis-reportes', [ReporteController::class, 'misReportes']);
        Route::get('/cancha/{canchaId}', [ReporteController::class, 'porCancha']);
        Route::post('/', [ReporteController::class, 'store']);
        Route::get('/{id}', [ReporteController::class, 'show']);
        Route::put('/{id}', [ReporteController::class, 'update']); // Admin
    });

    // Reportes de Jugadores
    Route::prefix('reportes-jugadores')->group(function () {
        Route::get('/', [ReporteJugadorController::class, 'index']);
        Route::post('/', [ReporteJugadorController::class, 'store']); // Solo dueños
        Route::get('/{id}', [ReporteJugadorController::class, 'show']);
        Route::put('/{id}', [ReporteJugadorController::class, 'update']); // Admin
    });

    // Usuarios (Admin)
    Route::prefix('usuarios')->group(function () {
        Route::get('/', [UsuarioController::class, 'index']);
        Route::get('/estadisticas', [UsuarioController::class, 'estadisticas']);
        Route::get('/solicitudes-pendientes', [UsuarioController::class, 'solicitudesPendientes']);
        Route::get('/{id}/estadisticas', [UsuarioController::class, 'obtenerEstadisticas']); // Estadísticas de usuario específico
        Route::post('/{id}/aprobar-solicitud', [UsuarioController::class, 'aprobarSolicitud']);
        Route::post('/{id}/rechazar-solicitud', [UsuarioController::class, 'rechazarSolicitud']);
        Route::get('/{id}', [UsuarioController::class, 'show']);
        Route::put('/{id}', [UsuarioController::class, 'update']);
    });

    // Sanciones y Reportes
    Route::prefix('sanciones')->group(function () {
        Route::get('/baneos', [SancionController::class, 'listarBaneos']);
        Route::post('/baneos', [SancionController::class, 'crearBaneo']);
        Route::put('/baneos/{id}/levantar', [SancionController::class, 'levantarBaneo']);
        
        Route::get('/reportes', [SancionController::class, 'listarReportes']);
        Route::post('/reportes', [SancionController::class, 'crearReporte']);
        Route::put('/reportes/{id}', [SancionController::class, 'actualizarReporte']);
    });
});

// Ruta dinámica {id} AL FINAL para que no capture rutas específicas como "mis-canchas"
Route::get('/canchas/{id}', [CanchaController::class, 'show']);
