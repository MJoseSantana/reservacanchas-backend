<?php

namespace App\Http\Controllers;

use App\Models\Usuario;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;

class UsuarioController extends Controller
{
    /**
     * Listar todos los usuarios (Admin)
     * GET /api/usuarios
     */
    public function index(Request $request)
    {
        try {
            $user = $request->user();

            if ($user->rol !== 'admin') {
                return response()->json([
                    'success' => false,
                    'message' => 'No tienes permisos de administrador'
                ], 403);
            }

            $query = Usuario::query();

            // Filtros opcionales
            if ($request->has('rol')) {
                $query->where('rol', $request->rol);
            }

            if ($request->has('estado')) {
                $query->where('estado', $request->estado);
            }

            $usuarios = $query->get()->makeHidden(['password']);

            return response()->json([
                'success' => true,
                'data' => $usuarios
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener usuarios: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener usuario por ID
     * GET /api/usuarios/{id}
     */
    public function show($id)
    {
        try {
            $usuario = Usuario::find($id);

            if (!$usuario) {
                return response()->json([
                    'success' => false,
                    'message' => 'Usuario no encontrado'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $usuario->makeHidden(['password'])
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener usuario: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Actualizar usuario (Admin)
     * PUT/PATCH /api/usuarios/{id}
     */
    public function update(Request $request, $id)
    {
        try {
            $user = $request->user();

            if ($user->rol !== 'admin') {
                return response()->json([
                    'success' => false,
                    'message' => 'No tienes permisos de administrador'
                ], 403);
            }

            $targetUser = Usuario::find($id);
            if (!$targetUser) {
                return response()->json([
                    'success' => false,
                    'message' => 'Usuario no encontrado'
                ], 404);
            }

            $validator = Validator::make($request->all(), [
                'nombre' => 'sometimes|string|max:100',
                'apellido' => 'sometimes|string|max:100',
                'telefono' => 'sometimes|string|max:20',
                'rol' => 'sometimes|in:jugador,dueno,admin',
                'estado' => 'sometimes|in:activo,baneado',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Datos de validación incorrectos',
                    'errors' => $validator->errors()
                ], 422);
            }

            $updateData = array_filter($request->only([
                'nombre', 'apellido', 'telefono', 'rol', 'estado'
            ]));

            $targetUser->update($updateData);

            return response()->json([
                'success' => true,
                'message' => 'Usuario actualizado exitosamente',
                'data' => $targetUser->makeHidden(['password'])
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar usuario: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Actualizar perfil del usuario autenticado
     * PUT/PATCH /api/usuarios/perfil
     */
    public function updatePerfil(Request $request)
    {
        try {
            $user = $request->user();

            $validator = Validator::make($request->all(), [
                'nombre' => 'sometimes|string|max:100',
                'apellido' => 'sometimes|string|max:100',
                'telefono' => 'sometimes|string|max:20',
                'fecha_nacimiento' => 'sometimes|date',
                'foto_perfil' => 'sometimes|url',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Datos de validación incorrectos',
                    'errors' => $validator->errors()
                ], 422);
            }

            $updateData = array_filter($request->only([
                'nombre', 'apellido', 'telefono', 'fecha_nacimiento', 'foto_perfil'
            ]));

            $user->update($updateData);

            return response()->json([
                'success' => true,
                'message' => 'Perfil actualizado exitosamente',
                'data' => $user->makeHidden(['password'])
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar perfil: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Cambiar contraseña
     * POST /api/usuarios/cambiar-password
     */
    public function cambiarPassword(Request $request)
    {
        try {
            $user = $request->user();

            $validator = Validator::make($request->all(), [
                'password_actual' => 'required|string',
                'password_nuevo' => 'required|string|min:6|confirmed',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Datos de validación incorrectos',
                    'errors' => $validator->errors()
                ], 422);
            }

            if (!Hash::check($request->password_actual, $user->password)) {
                return response()->json([
                    'success' => false,
                    'message' => 'La contraseña actual es incorrecta'
                ], 401);
            }

            $user->password = Hash::make($request->password_nuevo);
            $user->save();

            return response()->json([
                'success' => true,
                'message' => 'Contraseña actualizada exitosamente'
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al cambiar contraseña: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Listar solicitudes pendientes de dueños de cancha
     * GET /api/usuarios/solicitudes-pendientes
     */
    public function solicitudesPendientes(Request $request)
    {
        try {
            $user = $request->user();

            if ($user->rol !== 'admin') {
                return response()->json([
                    'success' => false,
                    'message' => 'No tienes permisos de administrador'
                ], 403);
            }

            // Obtener filtro de estado (por defecto: pendiente)
            $estado = $request->query('estado', 'pendiente');

            $query = Usuario::where('rol', 'dueno')
                ->with('canchas');

            // Filtrar por estado si se especifica
            if (in_array($estado, ['pendiente', 'activo', 'rechazado'])) {
                $query->where('estado', $estado);
            }

            $solicitudes = $query->get()->makeHidden(['password']);

            return response()->json([
                'success' => true,
                'data' => $solicitudes
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener solicitudes: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Aprobar solicitud de dueño de cancha
     * POST /api/usuarios/{id}/aprobar-solicitud
     */
    public function aprobarSolicitud(Request $request, $id)
    {
        try {
            $user = $request->user();

            if ($user->rol !== 'admin') {
                return response()->json([
                    'success' => false,
                    'message' => 'No tienes permisos de administrador'
                ], 403);
            }

            $usuario = Usuario::find($id);

            if (!$usuario) {
                return response()->json([
                    'success' => false,
                    'message' => 'Usuario no encontrado'
                ], 404);
            }

            if ($usuario->rol !== 'dueno') {
                return response()->json([
                    'success' => false,
                    'message' => 'Solo se pueden aprobar solicitudes de dueños de cancha'
                ], 400);
            }

            if ($usuario->estado !== 'pendiente') {
                return response()->json([
                    'success' => false,
                    'message' => 'La solicitud no está en estado pendiente'
                ], 400);
            }

            $usuario->estado = 'activo';
            $usuario->save();

            return response()->json([
                'success' => true,
                'message' => 'Solicitud aprobada exitosamente',
                'data' => $usuario->makeHidden(['password'])
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al aprobar solicitud: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Rechazar solicitud de dueño de cancha
     * POST /api/usuarios/{id}/rechazar-solicitud
     */
    public function rechazarSolicitud(Request $request, $id)
    {
        try {
            $user = $request->user();

            if ($user->rol !== 'admin') {
                return response()->json([
                    'success' => false,
                    'message' => 'No tienes permisos de administrador'
                ], 403);
            }

            $usuario = Usuario::find($id);

            if (!$usuario) {
                return response()->json([
                    'success' => false,
                    'message' => 'Usuario no encontrado'
                ], 404);
            }

            if ($usuario->rol !== 'dueno') {
                return response()->json([
                    'success' => false,
                    'message' => 'Solo se pueden rechazar solicitudes de dueños de cancha'
                ], 400);
            }

            if ($usuario->estado !== 'pendiente') {
                return response()->json([
                    'success' => false,
                    'message' => 'La solicitud no está en estado pendiente'
                ], 400);
            }

            // Marcar como rechazado en lugar de eliminar
            $usuario->estado = 'rechazado';
            $usuario->save();

            return response()->json([
                'success' => true,
                'message' => 'Solicitud rechazada exitosamente'
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al rechazar solicitud: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener estadísticas de un usuario específico
     * GET /api/usuarios/{id}/estadisticas
     */
    public function obtenerEstadisticas(Request $request, $id)
    {
        try {
            $user = $request->user();
            
            // Solo el propio usuario o un admin pueden ver las estadísticas
            if ($user->id != $id && $user->rol !== 'admin') {
                return response()->json([
                    'success' => false,
                    'message' => 'No tienes permisos para ver estas estadísticas'
                ], 403);
            }

            $usuario = Usuario::find($id);

            if (!$usuario) {
                return response()->json([
                    'success' => false,
                    'message' => 'Usuario no encontrado'
                ], 404);
            }

            // Obtener estadísticas según el rol
            if ($usuario->rol === 'jugador') {
                $reservas = \App\Models\Reservacion::where('usuario_id', $usuario->id)->get();
                
                $estadisticas = [
                    'totalReservas' => $reservas->count(),
                    'reservasActivas' => $reservas->where('estado', 'confirmada')->count(),
                    'reservasCanceladas' => $reservas->where('estado', 'cancelada')->count(),
                    'reservasCompletadas' => $reservas->where('estado', 'completada')->count(),
                    'totalGastado' => $reservas->whereIn('estado', ['confirmada', 'completada'])->sum('precio_total'),
                ];
            } elseif ($usuario->rol === 'dueno') {
                $canchas = \App\Models\Cancha::where('dueno_id', $usuario->id)->get();
                $canchasIds = $canchas->pluck('id');
                $reservas = \App\Models\Reservacion::whereIn('cancha_id', $canchasIds)->get();
                
                $estadisticas = [
                    'totalCanchas' => $canchas->count(),
                    'canchasActivas' => $canchas->where('estado', 'activa')->count(),
                    'totalReservas' => $reservas->count(),
                    'reservasActivas' => $reservas->where('estado', 'confirmada')->count(),
                    'totalIngresos' => $reservas->whereIn('estado', ['confirmada', 'completada'])->sum('precio_total'),
                ];
            } else {
                $estadisticas = [
                    'message' => 'Estadísticas no disponibles para este tipo de usuario'
                ];
            }

            return response()->json([
                'success' => true,
                'data' => $estadisticas
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener estadísticas: ' . $e->getMessage()
            ], 500);
        }
    }
}
