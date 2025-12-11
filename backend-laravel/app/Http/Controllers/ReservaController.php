<?php

namespace App\Http\Controllers;

use App\Models\Reservacion;
use App\Models\Cancha;
use App\Models\Usuario;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class ReservaController extends Controller
{
    /**
     * Listar todas las reservaciones
     * GET /api/reservaciones
     */
    public function index(Request $request)
    {
        try {
            $user = $request->user();
            
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Usuario no autenticado'
                ], 401);
            }

            $query = Reservacion::with(['cancha', 'usuario']);

            // Filtros según rol
            if ($user->rol === 'jugador') {
                $query->where('usuario_id', $user->id);
            } elseif ($user->rol === 'dueno') {
                $query->whereHas('cancha', function($q) use ($user) {
                    $q->where('dueno_id', $user->id);
                });
            }

            // Filtros adicionales
            if ($request->has('estado')) {
                $query->where('estado', $request->estado);
            }

            if ($request->has('cancha_id')) {
                $query->where('cancha_id', $request->cancha_id);
            }

            if ($request->has('fecha')) {
                $query->whereDate('fecha', $request->fecha);
            }

            $reservaciones = $query->orderBy('fecha', 'desc')
                                  ->orderBy('hora_inicio', 'desc')
                                  ->get();

            return response()->json([
                'success' => true,
                'data' => $reservaciones
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener reservaciones: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener mis reservas (usuario autenticado)
     * GET /api/reservas/mis-reservas
     */
    public function misReservas(Request $request)
    {
        try {
            $user = $request->user();
            
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Usuario no autenticado'
                ], 401);
            }

            $query = Reservacion::with(['cancha', 'usuario'])
                ->where('usuario_id', $user->id);

            // Filtros opcionales
            if ($request->has('estado')) {
                $query->where('estado', $request->estado);
            }

            if ($request->has('fecha_desde')) {
                $query->whereDate('fecha', '>=', $request->fecha_desde);
            }

            if ($request->has('fecha_hasta')) {
                $query->whereDate('fecha', '<=', $request->fecha_hasta);
            }

            $reservaciones = $query->orderBy('fecha', 'desc')
                                  ->orderBy('hora_inicio', 'desc')
                                  ->get();

            // Formatear respuesta con datos denormalizados
            $reservacionesFormateadas = $reservaciones->map(function($reserva) {
                return [
                    'id' => $reserva->id,
                    'canchaId' => $reserva->cancha_id,
                    'canchaNombre' => $reserva->cancha->nombre ?? null,
                    'canchaImagen' => $reserva->cancha->imagenes[0] ?? null,
                    'jugadorId' => $reserva->usuario_id,
                    'jugadorNombre' => $reserva->usuario->nombre . ' ' . $reserva->usuario->apellido,
                    'fecha' => $reserva->fecha,
                    'horaInicio' => $reserva->hora_inicio,
                    'horaFin' => $reserva->hora_fin,
                    'estado' => $reserva->estado,
                    'precioTotal' => $reserva->precio_total,
                    'metodoPago' => $reserva->metodo_pago,
                    'fechaCreacion' => $reserva->created_at,
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $reservacionesFormateadas
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener mis reservas: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener reservación por ID
     * GET /api/reservaciones/{id}
     */
    public function show($id)
    {
        try {
            $reservacion = Reservacion::with(['cancha', 'usuario'])->find($id);

            if (!$reservacion) {
                return response()->json([
                    'success' => false,
                    'message' => 'Reservación no encontrada'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $reservacion
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener reservación: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Crear nueva reservación
     * POST /api/reservaciones
     */
    public function store(Request $request)
    {
        try {
            $user = $request->user();

            $validator = Validator::make($request->all(), [
                'cancha_id' => 'required|exists:canchas,id',
                'fecha' => 'required|date|after_or_equal:today',
                'hora_inicio' => 'required|date_format:H:i',
                'hora_fin' => 'required|date_format:H:i|after:hora_inicio',
                'tipo_reserva' => 'required|in:unica,semanal',
                'semanas_reservadas' => 'required_if:tipo_reserva,semanal|integer|min:1|max:52',
                'metodo_pago' => 'nullable|string',
                'notas_cliente' => 'nullable|string',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Errores de validación',
                    'errors' => $validator->errors()
                ], 422);
            }

            $cancha = Cancha::find($request->cancha_id);

            // Verificar disponibilidad
            $conflicto = $this->verificarDisponibilidad(
                $request->cancha_id,
                $request->fecha,
                $request->hora_inicio,
                $request->hora_fin
            );

            if ($conflicto) {
                return response()->json([
                    'success' => false,
                    'message' => 'La cancha no está disponible en ese horario'
                ], 409);
            }

            // Calcular duración y precio
            $horaInicio = \Carbon\Carbon::createFromFormat('H:i', $request->hora_inicio);
            $horaFin = \Carbon\Carbon::createFromFormat('H:i', $request->hora_fin);
            $duracion = $horaFin->diffInHours($horaInicio, true);

            // Determinar si es horario nocturno
            $esNocturno = false;
            if ($cancha->hora_inicio_nocturno) {
                $inicioNocturno = \Carbon\Carbon::createFromFormat('H:i', $cancha->hora_inicio_nocturno);
                $esNocturno = $horaInicio->gte($inicioNocturno);
            }

            $precioPorHora = $esNocturno && $cancha->precio_nocturno 
                ? $cancha->precio_nocturno 
                : $cancha->precio_diurno;

            $precioTotal = $duracion * $precioPorHora;

            // Aplicar descuentos si los hay
            $descuentoPorcentaje = 0;
            // Aquí puedes implementar lógica de descuentos

            if ($descuentoPorcentaje > 0) {
                $precioTotal = $precioTotal * (1 - $descuentoPorcentaje / 100);
            }

            $reservacionData = [
                'cancha_id' => $request->cancha_id,
                'usuario_id' => $user->id,
                'fecha' => $request->fecha,
                'hora_inicio' => $request->hora_inicio,
                'hora_fin' => $request->hora_fin,
                'duracion_horas' => $duracion,
                'precio_por_hora' => $precioPorHora,
                'descuento_porcentaje' => $descuentoPorcentaje,
                'precio_total' => $precioTotal,
                'tipo_reserva' => $request->tipo_reserva,
                'semanas_reservadas' => $request->semanas_reservadas ?? 1,
                'estado' => 'pendiente',
                'metodo_pago' => $request->metodo_pago,
                'notas_cliente' => $request->notas_cliente,
            ];

            $reservacion = Reservacion::create($reservacionData);

            return response()->json([
                'success' => true,
                'message' => 'Reservación creada exitosamente',
                'data' => $reservacion->load(['cancha', 'usuario'])
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al crear reservación: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Actualizar estado de reservación
     * PATCH /api/reservaciones/{id}
     */
    public function update(Request $request, $id)
    {
        try {
            $user = $request->user();
            $reservacion = Reservacion::with('cancha')->find($id);

            if (!$reservacion) {
                return response()->json([
                    'success' => false,
                    'message' => 'Reservación no encontrada'
                ], 404);
            }

            // Verificar permisos
            $esDueno = $reservacion->cancha->dueno_id === $user->id;
            $esJugador = $reservacion->usuario_id === $user->id;

            if (!$esDueno && !$esJugador && $user->rol !== 'admin') {
                return response()->json([
                    'success' => false,
                    'message' => 'No tienes permisos para actualizar esta reservación'
                ], 403);
            }

            $validator = Validator::make($request->all(), [
                'estado' => 'sometimes|in:pendiente,confirmada,cancelada,completada',
                'notas_dueno' => 'nullable|string',
                'razon_cancelacion' => 'required_if:estado,cancelada|nullable|string',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Errores de validación',
                    'errors' => $validator->errors()
                ], 422);
            }

            $updateData = [];

            if ($request->has('estado')) {
                $updateData['estado'] = $request->estado;
                if ($request->estado === 'cancelada') {
                    $updateData['cancelada_en'] = now();
                }
            }

            if ($request->has('notas_dueno')) {
                $updateData['notas_dueno'] = $request->notas_dueno;
            }

            if ($request->has('razon_cancelacion')) {
                $updateData['razon_cancelacion'] = $request->razon_cancelacion;
            }

            $reservacion->update($updateData);

            return response()->json([
                'success' => true,
                'message' => 'Reservación actualizada exitosamente',
                'data' => $reservacion->fresh(['cancha', 'usuario'])
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar reservación: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Cancelar reservación
     * POST /api/reservaciones/{id}/cancelar
     */
    public function cancelar(Request $request, $id)
    {
        try {
            $user = $request->user();
            $reservacion = Reservacion::with('cancha')->find($id);

            if (!$reservacion) {
                return response()->json([
                    'success' => false,
                    'message' => 'Reservación no encontrada'
                ], 404);
            }

            // Verificar permisos
            $esDueno = $reservacion->cancha->dueno_id === $user->id;
            $esJugador = $reservacion->usuario_id === $user->id;

            if (!$esDueno && !$esJugador && $user->rol !== 'admin') {
                return response()->json([
                    'success' => false,
                    'message' => 'No tienes permisos para cancelar esta reservación'
                ], 403);
            }

            if ($reservacion->estado === 'cancelada') {
                return response()->json([
                    'success' => false,
                    'message' => 'La reservación ya está cancelada'
                ], 400);
            }

            $validator = Validator::make($request->all(), [
                'razon_cancelacion' => 'required|string',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Errores de validación',
                    'errors' => $validator->errors()
                ], 422);
            }

            $reservacion->update([
                'estado' => 'cancelada',
                'razon_cancelacion' => $request->razon_cancelacion,
                'cancelada_en' => now()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Reservación cancelada exitosamente',
                'data' => $reservacion->fresh(['cancha', 'usuario'])
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al cancelar reservación: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Verificar disponibilidad de cancha
     * POST /api/reservaciones/verificar-disponibilidad
     */
    /**
     * Obtener reservas por cancha
     * GET /api/canchas/{canchaId}/reservas
     */
    public function reservasPorCancha(Request $request, $canchaId)
    {
        try {
            $cancha = Cancha::find($canchaId);
            
            if (!$cancha) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cancha no encontrada'
                ], 404);
            }

            $query = Reservacion::with(['usuario'])
                ->where('cancha_id', $canchaId);

            // Filtros opcionales
            if ($request->has('estado')) {
                $query->where('estado', $request->estado);
            }

            if ($request->has('fecha')) {
                $query->whereDate('fecha', $request->fecha);
            }

            if ($request->has('fecha_desde') && $request->has('fecha_hasta')) {
                $query->whereBetween('fecha', [$request->fecha_desde, $request->fecha_hasta]);
            }

            $reservas = $query->orderBy('fecha', 'desc')
                             ->orderBy('hora_inicio', 'desc')
                             ->get();

            return response()->json([
                'success' => true,
                'data' => $reservas,
                'cancha' => $cancha->nombre
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener reservas: ' . $e->getMessage()
            ], 500);
        }
    }

    public function verificarDisponibilidadPublic(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'cancha_id' => 'required|exists:canchas,id',
                'fecha' => 'required|date',
                'hora_inicio' => 'required|date_format:H:i',
                'hora_fin' => 'required|date_format:H:i',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Errores de validación',
                    'errors' => $validator->errors()
                ], 422);
            }

            $disponible = !$this->verificarDisponibilidad(
                $request->cancha_id,
                $request->fecha,
                $request->hora_inicio,
                $request->hora_fin
            );

            return response()->json([
                'success' => true,
                'disponible' => $disponible
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al verificar disponibilidad: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Verificar si hay conflicto de horario (método privado)
     */
    private function verificarDisponibilidad($canchaId, $fecha, $horaInicio, $horaFin, $exceptoId = null)
    {
        $query = Reservacion::where('cancha_id', $canchaId)
            ->whereDate('fecha', $fecha)
            ->whereIn('estado', ['pendiente', 'confirmada'])
            ->where(function($q) use ($horaInicio, $horaFin) {
                $q->where(function($q2) use ($horaInicio, $horaFin) {
                    $q2->where('hora_inicio', '<', $horaFin)
                       ->where('hora_fin', '>', $horaInicio);
                });
            });

        if ($exceptoId) {
            $query->where('id', '!=', $exceptoId);
        }

        return $query->exists();
    }
}
