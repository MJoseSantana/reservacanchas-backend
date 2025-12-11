<?php

namespace App\Http\Controllers;

use App\Models\ReporteCancha;
use App\Models\Cancha;
use App\Models\Usuario;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ReporteController extends Controller
{
    /**
     * Listar todos los reportes
     * GET /api/reportes
     */
    public function index(Request $request)
    {
        try {
            $user = $request->user();

            $query = ReporteCancha::with(['cancha', 'usuarioReportante', 'revisadoPor']);

            // Solo admins ven todos los reportes
            if ($user->rol !== 'admin') {
                $query->where('usuario_reportante_id', $user->id);
            }

            // Filtros
            if ($request->has('estado')) {
                $query->where('estado', $request->estado);
            }

            if ($request->has('cancha_id')) {
                $query->where('cancha_id', $request->cancha_id);
            }

            $reportes = $query->orderBy('created_at', 'desc')->get();

            return response()->json([
                'success' => true,
                'data' => $reportes
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener reportes: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener reporte por ID
     * GET /api/reportes/{id}
     */
    public function show($id)
    {
        try {
            $reporte = ReporteCancha::with(['cancha', 'usuarioReportante', 'revisadoPor'])->find($id);

            if (!$reporte) {
                return response()->json([
                    'success' => false,
                    'message' => 'Reporte no encontrado'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $reporte
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener reporte: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Crear nuevo reporte
     * POST /api/reportes
     */
    public function store(Request $request)
    {
        try {
            $user = $request->user();

            $validator = Validator::make($request->all(), [
                'cancha_id' => 'required|exists:canchas,id',
                'tipo_reporte' => 'required|string',
                'descripcion' => 'required|string',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Errores de validación',
                    'errors' => $validator->errors()
                ], 422);
            }

            $reporteData = [
                'cancha_id' => $request->cancha_id,
                'usuario_reportante_id' => $user->id,
                'razon' => $request->tipo_reporte,
                'descripcion' => $request->descripcion,
                'estado' => 'pendiente',
            ];

            $reporte = ReporteCancha::create($reporteData);

            return response()->json([
                'success' => true,
                'message' => 'Reporte creado exitosamente',
                'data' => $reporte->load(['cancha', 'usuarioReportante'])
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al crear reporte: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Actualizar estado de reporte (Admin)
     * PATCH /api/reportes/{id}
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

            $reporte = ReporteCancha::find($id);

            if (!$reporte) {
                return response()->json([
                    'success' => false,
                    'message' => 'Reporte no encontrado'
                ], 404);
            }

            $validator = Validator::make($request->all(), [
                'estado' => 'required|in:pendiente,revisado,resuelto',
                'accion_tomada' => 'nullable|string',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Errores de validación',
                    'errors' => $validator->errors()
                ], 422);
            }

            $reporte->update([
                'estado' => $request->estado,
                'accion_tomada' => $request->accion_tomada,
                'admin_id' => $user->id,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Reporte actualizado exitosamente',
                'data' => $reporte->fresh(['cancha', 'usuario', 'admin'])
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar reporte: ' . $e->getMessage()
            ], 500);
        }
    }
}
