<?php

namespace App\Http\Controllers;

use App\Models\Calificacion;
use App\Models\Cancha;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class CalificacionController extends Controller
{
    /**
     * Listar todas las calificaciones
     * GET /api/calificaciones
     */
    public function index(Request $request)
    {
        try {
            $query = Calificacion::with(['cancha', 'usuario']);

            // Filtros
            if ($request->has('cancha_id')) {
                $query->where('cancha_id', $request->cancha_id);
            }

            if ($request->has('usuario_id')) {
                $query->where('usuario_id', $request->usuario_id);
            }

            $calificaciones = $query->orderBy('created_at', 'desc')->get();

            return response()->json([
                'success' => true,
                'data' => $calificaciones
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener calificaciones: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener calificación por ID
     * GET /api/calificaciones/{id}
     */
    public function show($id)
    {
        try {
            $calificacion = Calificacion::with(['cancha', 'usuario'])->find($id);

            if (!$calificacion) {
                return response()->json([
                    'success' => false,
                    'message' => 'Calificación no encontrada'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $calificacion
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener calificación: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener calificaciones por cancha
     * GET /api/canchas/{canchaId}/calificaciones
     */
    public function porCancha($canchaId)
    {
        try {
            $calificaciones = Calificacion::with('usuario')
                ->where('cancha_id', $canchaId)
                ->orderBy('created_at', 'desc')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $calificaciones
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener calificaciones: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Crear nueva calificación
     * POST /api/calificaciones
     */
    public function store(Request $request)
    {
        try {
            $user = $request->user();

            $validator = Validator::make($request->all(), [
                'cancha_id' => 'required|exists:canchas,id',
                'puntuacion' => 'required|integer|min:1|max:5',
                'comentario' => 'nullable|string|max:1000',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Errores de validación',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Verificar si el usuario ya calificó esta cancha
            $existente = Calificacion::where('usuario_id', $user->id)
                ->where('cancha_id', $request->cancha_id)
                ->first();

            if ($existente) {
                // Actualizar calificación existente
                $existente->update([
                    'calificacion' => $request->puntuacion,
                    'comentario' => $request->comentario,
                ]);

                // Actualizar promedio de la cancha
                $this->actualizarPromedioCancha($request->cancha_id);

                return response()->json([
                    'success' => true,
                    'message' => 'Calificación actualizada exitosamente',
                    'data' => $existente->load('usuario')
                ], 200);
            }

            // Crear nueva calificación
            $calificacion = Calificacion::create([
                'usuario_id' => $user->id,
                'cancha_id' => $request->cancha_id,
                'calificacion' => $request->puntuacion,
                'comentario' => $request->comentario,
            ]);

            // Actualizar promedio de la cancha
            $this->actualizarPromedioCancha($request->cancha_id);

            return response()->json([
                'success' => true,
                'message' => 'Calificación creada exitosamente',
                'data' => $calificacion->load('usuario')
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al crear calificación: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Actualizar calificación
     * PUT /api/calificaciones/{id}
     */
    public function update(Request $request, $id)
    {
        try {
            $user = $request->user();
            $calificacion = Calificacion::find($id);

            if (!$calificacion) {
                return response()->json([
                    'success' => false,
                    'message' => 'Calificación no encontrada'
                ], 404);
            }

            // Verificar que sea el propietario
            if ($calificacion->usuario_id !== $user->id && $user->rol !== 'admin') {
                return response()->json([
                    'success' => false,
                    'message' => 'No tienes permiso para editar esta calificación'
                ], 403);
            }

            $validator = Validator::make($request->all(), [
                'puntuacion' => 'nullable|integer|min:1|max:5',
                'comentario' => 'nullable|string|max:1000',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Errores de validación',
                    'errors' => $validator->errors()
                ], 422);
            }

            $updateData = [];
            if ($request->has('puntuacion')) {
                $updateData['calificacion'] = $request->puntuacion;
            }
            if ($request->has('comentario')) {
                $updateData['comentario'] = $request->comentario;
            }

            $calificacion->update($updateData);

            // Actualizar promedio de la cancha
            $this->actualizarPromedioCancha($calificacion->cancha_id);

            return response()->json([
                'success' => true,
                'message' => 'Calificación actualizada exitosamente',
                'data' => $calificacion->fresh('usuario')
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar calificación: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Eliminar calificación
     * DELETE /api/calificaciones/{id}
     */
    public function destroy(Request $request, $id)
    {
        try {
            $user = $request->user();
            $calificacion = Calificacion::find($id);

            if (!$calificacion) {
                return response()->json([
                    'success' => false,
                    'message' => 'Calificación no encontrada'
                ], 404);
            }

            // Verificar que sea el propietario o admin
            if ($calificacion->usuario_id !== $user->id && $user->rol !== 'admin') {
                return response()->json([
                    'success' => false,
                    'message' => 'No tienes permiso para eliminar esta calificación'
                ], 403);
            }

            $canchaId = $calificacion->cancha_id;
            $calificacion->delete();

            // Actualizar promedio de la cancha
            $this->actualizarPromedioCancha($canchaId);

            return response()->json([
                'success' => true,
                'message' => 'Calificación eliminada exitosamente'
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar calificación: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Actualizar el promedio de calificación de una cancha
     */
    private function actualizarPromedioCancha($canchaId)
    {
        $promedio = Calificacion::where('cancha_id', $canchaId)
            ->avg('calificacion');

        $totalCalificaciones = Calificacion::where('cancha_id', $canchaId)
            ->count();

        Cancha::where('id', $canchaId)->update([
            'calificacion_promedio' => $promedio ?? 0,
            'numero_calificaciones' => $totalCalificaciones,
        ]);
    }
}
