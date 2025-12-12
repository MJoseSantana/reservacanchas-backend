<?php

namespace App\Http\Controllers;

use App\Models\Cancha;
use App\Models\Usuario;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;

class CanchaController extends Controller
{
    /**
     * Método helper para convertir URLs de imágenes a absolutas
     */
    private function convertirImagenesAbsolutas($imagenes)
    {
        if (!$imagenes || !is_array($imagenes)) {
            return [];
        }

        return array_map(function($url) {
            // Si ya es URL absoluta, retornar como está
            if (str_starts_with($url, 'http://') || str_starts_with($url, 'https://')) {
                return $url;
            }
            
            // Si es relativa, convertir a absoluta
            return url($url);
        }, $imagenes);
    }

    /**
     * Listar todas las canchas
     * GET /api/canchas
     */
    public function index(Request $request)
    {
        try {
            $query = Cancha::with('dueno:id,nombre,apellido,email');

            // Filtros opcionales
            if ($request->has('ciudad')) {
                $query->where('ciudad', $request->ciudad);
            }

            if ($request->has('tipo_deporte')) {
                $query->where('tipo_deporte', $request->tipo_deporte);
            }

            if ($request->has('estado')) {
                $query->where('estado', $request->estado);
            } else {
                // Por defecto, solo mostrar canchas activas
                $query->where('estado', 'activa');
            }

            // Filtro por dueño
            if ($request->has('dueno_id')) {
                $query->where('dueno_id', $request->dueno_id);
            }

            $canchas = $query->get();
            
            // Convertir URLs de imágenes a absolutas
            $canchas->transform(function($cancha) {
                $cancha->imagenes = $this->convertirImagenesAbsolutas($cancha->imagenes);
                return $cancha;
            });

            return response()->json([
                'success' => true,
                'data' => $canchas
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener canchas: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener cancha por ID
     * GET /api/canchas/{id}
     */
    public function show($id)
    {
        try {
            $cancha = Cancha::with('dueno:id,nombre,apellido,email,telefono')->find($id);

            if (!$cancha) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cancha no encontrada'
                ], 404);
            }
            
            // Convertir URLs de imágenes a absolutas
            $cancha->imagenes = $this->convertirImagenesAbsolutas($cancha->imagenes);

            return response()->json([
                'success' => true,
                'data' => $cancha
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener cancha: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener canchas del dueño autenticado
     * GET /api/canchas/dueno/mis-canchas
     */
    public function misCanchas(Request $request)
    {
        try {
            $user = $request->user();

            \Log::info('=== MIS CANCHAS - DEBUG ===');
            \Log::info('Usuario autenticado:', [
                'id' => $user ? $user->id : 'NULL',
                'email' => $user ? $user->email : 'NULL',
                'rol' => $user ? $user->rol : 'NULL',
                'nombre' => $user ? $user->nombre : 'NULL'
            ]);

            if (!$user) {
                \Log::warning('Usuario no autenticado en misCanchas');
                return response()->json([
                    'success' => false,
                    'message' => 'Usuario no autenticado'
                ], 401);
            }

            if ($user->rol !== 'dueno') {
                \Log::warning('Usuario no es dueño:', ['rol' => $user->rol]);
                return response()->json([
                    'success' => false,
                    'message' => 'Solo los dueños pueden acceder a sus canchas'
                ], 403);
            }

            $canchas = Cancha::where('dueno_id', $user->id)->get();
            
            \Log::info('Canchas encontradas:', [
                'total' => $canchas->count(),
                'dueno_id_buscado' => $user->id,
                'ids' => $canchas->pluck('id')->toArray(),
                'nombres' => $canchas->pluck('nombre')->toArray()
            ]);
            
            // Si no hay canchas, verificar si existen en la BD
            if ($canchas->count() === 0) {
                $totalCanchas = Cancha::count();
                $canchasConDueno = Cancha::whereNotNull('dueno_id')->pluck('dueno_id', 'nombre')->toArray();
                \Log::warning('No se encontraron canchas para el dueño', [
                    'total_canchas_bd' => $totalCanchas,
                    'canchas_con_dueno' => $canchasConDueno
                ]);
            }
            
            // Convertir URLs de imágenes a absolutas
            $canchas->transform(function($cancha) {
                $cancha->imagenes = $this->convertirImagenesAbsolutas($cancha->imagenes);
                return $cancha;
            });

            return response()->json([
                'success' => true,
                'data' => $canchas
            ], 200);

        } catch (\Exception $e) {
            \Log::error('Error en misCanchas:', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener canchas: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Crear nueva cancha
     * POST /api/canchas
     */
    public function store(Request $request)
    {
        try {
            $user = $request->user();
            
            // Log detallado para debugging
            \Log::info('=== CREAR CANCHA ===');
            \Log::info('Usuario:', ['id' => $user->id, 'email' => $user->email, 'rol' => $user->rol]);
            \Log::info('Datos recibidos:', $request->all());

            if ($user->rol !== 'dueno') {
                return response()->json([
                    'success' => false,
                    'message' => 'Solo los dueños pueden crear canchas'
                ], 403);
            }

            $validator = Validator::make($request->all(), [
                'nombre' => 'required|string|max:255',
                'descripcion' => 'nullable|string',
                'direccion' => 'required|string',
                'barrio' => 'nullable|string',
                'distrito' => 'nullable|string',
                'provincia' => 'nullable|string',
                'pais' => 'nullable|string',
                'ciudad' => 'required|string',
                'referencia' => 'nullable|string',
                'latitud' => 'nullable|numeric',
                'longitud' => 'nullable|numeric',
                'tipo_deporte' => 'required|string',
                'tipo_superficie' => 'required|string',
                'jugadores_maximos' => 'required|integer|min:2',
                'aforo_espectadores' => 'nullable|integer|min:0',
                'precio_diurno' => 'required|numeric|min:0',
                'precio_nocturno' => 'nullable|numeric|min:0',
                'hora_inicio_nocturno' => 'nullable|string',
                'hora_apertura' => 'nullable|string',
                'hora_cierre' => 'nullable|string',
                'horarios_por_dia' => 'nullable|array',
                'descuentos' => 'nullable|array',
                'servicios' => 'nullable|array',
                'imagenes' => 'nullable|array',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Errores de validación',
                    'errors' => $validator->errors()
                ], 422);
            }

            $canchaData = [
                'dueno_id' => $user->id,
                'nombre' => $request->nombre,
                'descripcion' => $request->descripcion,
                'direccion' => $request->direccion,
                'distrito' => $request->barrio ?? $request->distrito, // Aceptar barrio o distrito
                'ciudad' => $request->ciudad,
                'provincia' => $request->provincia,
                'pais' => $request->pais ?: null,
                'latitud' => $request->latitud,
                'longitud' => $request->longitud,
                'tipo_deporte' => $request->tipo_deporte,
                'tipo_superficie' => $request->tipo_superficie, // ✅ Corregido: usar tipo_superficie del frontend
                'jugadores_maximos' => $request->jugadores_maximos, // ✅ Corregido: usar jugadores_maximos del frontend
                'aforo_espectadores' => $request->aforo_espectadores ?? 0,
                'precio_diurno' => $request->precio_diurno,
                'precio_nocturno' => $request->precio_nocturno ?? $request->precio_diurno,
                'hora_inicio_nocturno' => $request->hora_inicio_nocturno ?? '18:00',
                'hora_apertura' => $request->hora_apertura ?? '06:00',
                'hora_cierre' => $request->hora_cierre ?? '23:00',
                'referencia' => $request->referencia,
                'horarios_por_dia' => $request->horarios_por_dia ?? [],
                'descuentos' => $request->descuentos ?? [],
                'servicios' => $request->servicios ?? [],
                'imagenes' => [], // Inicializar como array vacío
                'estado' => 'activa',
            ];
            
            \Log::info('Datos mapeados para crear cancha:', $canchaData);

            $cancha = Cancha::create($canchaData);
            
            \Log::info('Cancha creada exitosamente:', ['id' => $cancha->id, 'nombre' => $cancha->nombre]);

            return response()->json([
                'success' => true,
                'message' => 'Cancha creada exitosamente',
                'data' => $cancha
            ], 201);

        } catch (\Exception $e) {
            \Log::error('Error al crear cancha:', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Error al crear cancha: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Crear cancha durante el proceso de registro (sin autenticación)
     * POST /api/canchas/registro
     */
    public function storeFromRegistration(Request $request)
    {
        try {
            // Log para debugging
            \Log::info('=== CREAR CANCHA DESDE REGISTRO ===');
            \Log::info('Datos recibidos:', $request->all());
            
            $validator = Validator::make($request->all(), [
                'dueno_id' => 'required|exists:usuarios,id',
                'nombre' => 'required|string|max:255',
                'descripcion' => 'nullable|string',
                'direccion' => 'required|string',
                'barrio' => 'nullable|string',
                'provincia' => 'nullable|string',
                'pais' => 'nullable|string',
                'ciudad' => 'required|string',
                'latitud' => 'nullable|numeric',
                'longitud' => 'nullable|numeric',
                'tipo_deporte' => 'required|string',
                'tipo_superficie' => 'required|string', // ✅ Cambiado de 'superficie'
                'jugadores_maximos' => 'required|integer|min:2', // ✅ Cambiado de 'jugadores_max'
                'aforo_espectadores' => 'nullable|integer|min:0',
                'precio_diurno' => 'required|numeric|min:0',
                'precio_nocturno' => 'nullable|numeric|min:0',
                'hora_inicio_nocturno' => 'nullable|string',
                'hora_apertura' => 'nullable|string',
                'hora_cierre' => 'nullable|string',
                'referencia' => 'nullable|string',
                'horarios_por_dia' => 'nullable',
                'descuentos' => 'nullable',
                'servicios' => 'nullable',
            ]);

            if ($validator->fails()) {
                \Log::error('Errores de validación:', $validator->errors()->toArray());
                return response()->json([
                    'success' => false,
                    'message' => 'Errores de validación',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Verificar que el usuario sea dueño y esté pendiente
            $usuario = Usuario::find($request->dueno_id);
            \Log::info('Usuario encontrado:', ['id' => $usuario?->id, 'rol' => $usuario?->rol]);
            
            if (!$usuario || $usuario->rol !== 'dueno') {
                return response()->json([
                    'success' => false,
                    'message' => 'El usuario debe tener rol de dueño'
                ], 403);
            }

            $canchaData = [
                'dueno_id' => $request->dueno_id,
                'nombre' => $request->nombre,
                'descripcion' => $request->descripcion,
                'direccion' => $request->direccion,
                'distrito' => $request->barrio ?? $request->distrito, // Aceptar barrio o distrito
                'ciudad' => $request->ciudad,
                'provincia' => $request->provincia,
                'pais' => $request->pais ?: null,
                'latitud' => $request->latitud,
                'longitud' => $request->longitud,
                'tipo_deporte' => $request->tipo_deporte,
                'tipo_superficie' => $request->tipo_superficie, // ✅ Corregido
                'jugadores_maximos' => $request->jugadores_maximos, // ✅ Corregido
                'aforo_espectadores' => $request->aforo_espectadores ?? 0,
                'precio_diurno' => $request->precio_diurno,
                'precio_nocturno' => $request->precio_nocturno ?? $request->precio_diurno,
                'hora_inicio_nocturno' => $request->hora_inicio_nocturno ?? '18:00',
                'hora_apertura' => $request->hora_apertura ?? '06:00',
                'hora_cierre' => $request->hora_cierre ?? '23:00',
                'referencia' => $request->referencia,
                'horarios_por_dia' => $request->horarios_por_dia ?? [],
                'descuentos' => $request->descuentos ?? [],
                'servicios' => $request->servicios ?? [],
                'imagenes' => [], // Inicializar como array vacío
                'estado' => 'activa',
            ];

            \Log::info('Datos mapeados para crear cancha:', $canchaData);

            $cancha = Cancha::create($canchaData);
            
            \Log::info('Cancha creada exitosamente:', ['id' => $cancha->id, 'nombre' => $cancha->nombre]);

            return response()->json([
                'success' => true,
                'message' => 'Cancha registrada exitosamente',
                'data' => $cancha
            ], 201);

        } catch (\Exception $e) {
            \Log::error('Error al crear cancha desde registro:', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Error al registrar cancha: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Actualizar cancha
     * PUT/PATCH /api/canchas/{id}
     */
    public function update(Request $request, $id)
    {
        try {
            $user = $request->user();
            $cancha = Cancha::find($id);

            if (!$cancha) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cancha no encontrada'
                ], 404);
            }

            // Verificar que el usuario sea el dueño o admin
            if ($user->rol !== 'admin' && $cancha->dueno_id !== $user->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'No tienes permisos para editar esta cancha'
                ], 403);
            }

            $validator = Validator::make($request->all(), [
                'nombre' => 'sometimes|string|max:255',
                'descripcion' => 'nullable|string',
                'direccion' => 'sometimes|string',
                'distrito' => 'nullable|string',
                'provincia' => 'sometimes|string',
                'pais' => 'nullable|string',
                'ciudad' => 'sometimes|string',
                'latitud' => 'sometimes|numeric',
                'longitud' => 'sometimes|numeric',
                'tipo_deporte' => 'sometimes|string',
                'tipo_superficie' => 'sometimes|string',
                'jugadores_maximos' => 'sometimes|integer|min:2',
                'aforo_espectadores' => 'nullable|integer|min:0',
                'precio_diurno' => 'sometimes|numeric|min:0',
                'precio_nocturno' => 'nullable|numeric|min:0',
                'hora_inicio_nocturno' => 'nullable|date_format:H:i',
                'hora_apertura' => 'sometimes|date_format:H:i',
                'hora_cierre' => 'sometimes|date_format:H:i',
                'horarios_por_dia' => 'nullable|array',
                'descuentos' => 'nullable|array',
                'servicios' => 'nullable|array',
                'imagenes' => 'nullable|array',
                'estado' => 'sometimes|in:activa,inactiva',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Errores de validación',
                    'errors' => $validator->errors()
                ], 422);
            }

            $cancha->update($request->all());

            return response()->json([
                'success' => true,
                'message' => 'Cancha actualizada exitosamente',
                'data' => $cancha->fresh()
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar cancha: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Eliminar cancha
     * DELETE /api/canchas/{id}
     */
    public function destroy(Request $request, $id)
    {
        try {
            $user = $request->user();
            $cancha = Cancha::find($id);

            if (!$cancha) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cancha no encontrada'
                ], 404);
            }

            // Verificar permisos
            if ($user->rol !== 'admin' && $cancha->dueno_id !== $user->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'No tienes permisos para eliminar esta cancha'
                ], 403);
            }

            $cancha->delete();

            return response()->json([
                'success' => true,
                'message' => 'Cancha eliminada exitosamente'
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar cancha: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Subir imagen de cancha
     * POST /api/canchas/{id}/imagen
     */
    public function uploadImage(Request $request, $id)
    {
        try {
            $user = $request->user();
            $cancha = Cancha::find($id);

            if (!$cancha) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cancha no encontrada'
                ], 404);
            }

            if ($user->rol !== 'admin' && $cancha->dueno_id !== $user->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'No tienes permisos para editar esta cancha'
                ], 403);
            }

            $validator = Validator::make($request->all(), [
                'imagen' => 'required|image|mimes:jpeg,png,jpg|max:2048',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Errores de validación',
                    'errors' => $validator->errors()
                ], 422);
            }

            $image = $request->file('imagen');
            $path = $image->store('canchas', 'public');
            
            // Generar URL absoluta con dominio fijo de Railway
            $url = 'https://web-production-117f.up.railway.app/storage/' . $path;

            // Agregar a la lista de imágenes
            $imagenes = $cancha->imagenes ?? [];
            $imagenes[] = $url;
            $cancha->imagenes = $imagenes;
            $cancha->save();

            return response()->json([
                'success' => true,
                'message' => 'Imagen subida exitosamente',
                'data' => [
                    'url' => $url,
                    'imagenes' => $imagenes
                ]
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al subir imagen: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Buscar canchas cercanas
     * POST /api/canchas/buscar-cercanas
     */
    public function buscarCercanas(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'latitud' => 'required|numeric',
                'longitud' => 'required|numeric',
                'radio' => 'nullable|numeric|min:1',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Errores de validación',
                    'errors' => $validator->errors()
                ], 422);
            }

            $lat = $request->latitud;
            $lon = $request->longitud;
            $radio = $request->radio ?? 10; // 10 km por defecto

            // Usar fórmula de Haversine para calcular distancia
            $canchas = Cancha::where('estado', 'activa')
                ->selectRaw("
                    *,
                    (6371 * acos(cos(radians(?)) * cos(radians(latitud)) * cos(radians(longitud) - radians(?)) + sin(radians(?)) * sin(radians(latitud)))) AS distancia
                ", [$lat, $lon, $lat])
                ->having('distancia', '<', $radio)
                ->orderBy('distancia')
                ->get();
            
            // Convertir URLs de imágenes a absolutas
            $canchas->transform(function($cancha) {
                $cancha->imagenes = $this->convertirImagenesAbsolutas($cancha->imagenes);
                return $cancha;
            });

            return response()->json([
                'success' => true,
                'data' => $canchas
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al buscar canchas: ' . $e->getMessage()
            ], 500);
        }
    }
}
