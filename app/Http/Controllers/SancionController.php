<?php

namespace App\Http\Controllers;

use App\Models\Baneo;
use App\Models\Usuario;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class SancionController extends Controller
{
    /**
     * Listar todas las sanciones
     * GET /api/sanciones
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

            $query = Baneo::with(['usuario', 'admin']);

            // Filtros
            if ($request->has('activo')) {
                $query->where('activo', $request->activo === 'true');
            }

            if ($request->has('tipo_baneo')) {
                $query->where('tipo_baneo', $request->tipo_baneo);
            }

            $sanciones = $query->orderBy('created_at', 'desc')->get();

            return response()->json([
                'success' => true,
                'data' => $sanciones
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener sanciones: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener sanción por ID
     * GET /api/sanciones/{id}
     */
    public function show($id)
    {
        try {
            $sancion = Baneo::with(['usuario', 'admin'])->find($id);

            if (!$sancion) {
                return response()->json([
                    'success' => false,
                    'message' => 'Sanción no encontrada'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $sancion
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener sanción: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Crear nueva sanción
     * POST /api/sanciones
     */
    public function store(Request $request)
    {
        try {
            $user = $request->user();

            if ($user->rol !== 'admin') {
                return response()->json([
                    'success' => false,
                    'message' => 'No tienes permisos de administrador'
                ], 403);
            }

            $validator = Validator::make($request->all(), [
                'usuario_id' => 'required|exists:usuarios,id',
                'tipo_baneo' => 'required|in:permanente,temporal',
                'razon' => 'required|string',
                'duracion_dias' => 'required_if:tipo_baneo,temporal|integer|min:1',
                'notas_admin' => 'nullable|string',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Errores de validación',
                    'errors' => $validator->errors()
                ], 422);
            }

            $sancionData = [
                'usuario_id' => $request->usuario_id,
                'tipo_baneo' => $request->tipo_baneo,
                'razon' => $request->razon,
                'duracion_dias' => $request->duracion_dias,
                'admin_id' => $user->id,
                'notas_admin' => $request->notas_admin,
                'activo' => true,
            ];

            if ($request->tipo_baneo === 'temporal' && $request->duracion_dias) {
                $sancionData['expira_en'] = now()->addDays($request->duracion_dias);
            }

            $sancion = Baneo::create($sancionData);

            // Actualizar estado del usuario
            $usuario = Usuario::find($request->usuario_id);
            $usuario->estado = 'baneado';
            $usuario->save();

            return response()->json([
                'success' => true,
                'message' => 'Sanción creada exitosamente',
                'data' => $sancion->load(['usuario', 'admin'])
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al crear sanción: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Levantar sanción
     * POST /api/sanciones/{id}/levantar
     */
    public function levantar(Request $request, $id)
    {
        try {
            $user = $request->user();

            if ($user->rol !== 'admin') {
                return response()->json([
                    'success' => false,
                    'message' => 'No tienes permisos de administrador'
                ], 403);
            }

            $sancion = Baneo::find($id);

            if (!$sancion) {
                return response()->json([
                    'success' => false,
                    'message' => 'Sanción no encontrada'
                ], 404);
            }

            if (!$sancion->activo) {
                return response()->json([
                    'success' => false,
                    'message' => 'La sanción ya está levantada'
                ], 400);
            }

            $sancion->update([
                'activo' => false,
                'levantado_en' => now(),
                'levantado_por_id' => $user->id,
            ]);

            // Reactivar usuario si no tiene otras sanciones activas
            $otrosBaneos = Baneo::where('usuario_id', $sancion->usuario_id)
                ->where('activo', true)
                ->where('id', '!=', $id)
                ->exists();

            if (!$otrosBaneos) {
                $usuario = Usuario::find($sancion->usuario_id);
                $usuario->estado = 'activo';
                $usuario->save();
            }

            return response()->json([
                'success' => true,
                'message' => 'Sanción levantada exitosamente',
                'data' => $sancion->fresh(['usuario', 'admin'])
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al levantar sanción: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Verificar si un usuario está baneado
     * GET /api/sanciones/verificar/{usuario_id}
     */
    public function verificarBaneo($usuarioId)
    {
        try {
            $baneoActivo = Baneo::where('usuario_id', $usuarioId)
                ->where('activo', true)
                ->where(function($query) {
                    $query->whereNull('expira_en')
                        ->orWhere('expira_en', '>', now());
                })
                ->with('admin')
                ->first();

            return response()->json([
                'success' => true,
                'baneado' => $baneoActivo !== null,
                'data' => $baneoActivo
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al verificar baneo: ' . $e->getMessage()
            ], 500);
        }
    }
}
