<?php

namespace App\Http\Controllers;

use App\Models\ReporteJugador;
use App\Models\Reservacion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ReporteJugadorController extends Controller
{
    /**
     * Listar todos los reportes de jugadores
     * GET /api/reportes-jugadores
     */
    public function index(Request $request)
    {
        try {
            $user = $request->user();

            $query = ReporteJugador::with(['jugador', 'dueno', 'reserva', 'admin']);

            // Filtrar según rol
            if ($user->rol === 'dueno') {
                // Dueños solo ven sus propios reportes
                $query->where('dueno_id', $user->id);
            } elseif ($user->rol !== 'admin') {
                // Jugadores pueden ver reportes en su contra
                $query->where('jugador_id', $user->id);
            }
            // Admin ve todos

            // Filtros opcionales
            if ($request->has('estado')) {
                $query->where('estado', $request->estado);
            }

            if ($request->has('jugador_id')) {
                $query->where('jugador_id', $request->jugador_id);
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
     * GET /api/reportes-jugadores/{id}
     */
    public function show($id, Request $request)
    {
        try {
            $reporte = ReporteJugador::with(['jugador', 'dueno', 'reserva', 'admin'])->find($id);

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
     * Crear nuevo reporte de jugador (solo dueños)
     * POST /api/reportes-jugadores
     */
    public function store(Request $request)
    {
        try {
            $user = $request->user();

            // Solo dueños pueden reportar jugadores
            if ($user->rol !== 'dueno') {
                return response()->json([
                    'success' => false,
                    'message' => 'Solo los dueños de canchas pueden reportar jugadores'
                ], 403);
            }

            $validator = Validator::make($request->all(), [
                'jugador_id' => 'required|exists:usuarios,id',
                'reserva_id' => 'nullable|exists:reservas,id',
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

            // Validar que la reserva pertenezca a una cancha del dueño
            if ($request->reserva_id) {
                $reserva = Reservacion::with('cancha')->find($request->reserva_id);
                if (!$reserva || $reserva->cancha->dueno_id != $user->id) {
                    return response()->json([
                        'success' => false,
                        'message' => 'No tienes permisos para reportar esta reserva'
                    ], 403);
                }
            }

            $reporteData = [
                'jugador_id' => $request->jugador_id,
                'dueno_id' => $user->id,
                'reserva_id' => $request->reserva_id,
                'tipo_reporte' => $request->tipo_reporte,
                'descripcion' => $request->descripcion,
                'estado' => 'pendiente',
            ];

            $reporte = ReporteJugador::create($reporteData);

            return response()->json([
                'success' => true,
                'message' => 'Reporte creado exitosamente',
                'data' => $reporte->load(['jugador', 'dueno', 'reserva'])
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
     * PUT /api/reportes-jugadores/{id}
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

            $reporte = ReporteJugador::find($id);

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
                'fecha_revision' => now(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Reporte actualizado exitosamente',
                'data' => $reporte->fresh(['jugador', 'dueno', 'reserva', 'admin'])
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar reporte: ' . $e->getMessage()
            ], 500);
        }
    }
}
