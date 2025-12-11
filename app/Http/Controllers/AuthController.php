<?php

namespace App\Http\Controllers;

use App\Models\Usuario;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class AuthController extends Controller
{
    /**
     * Registrar nuevo usuario - POST /api/auth/register
     */
    public function register(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'email' => 'required|email|unique:usuarios,email',
                'password' => 'required|min:6',
                'nombre' => 'required|string|max:100',
                'apellido' => 'required|string|max:100',
                'telefono' => 'required|string|max:20',
                'rol' => 'required|in:jugador,dueno,admin',
                'fecha_nacimiento' => 'nullable|date',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Datos de validación incorrectos',
                    'errors' => $validator->errors()
                ], 422);
            }

            $usuario = Usuario::create([
                'email' => $request->email,
                'password' => $request->password,
                'nombre' => $request->nombre,
                'apellido' => $request->apellido,
                'telefono' => $request->telefono,
                'fecha_nacimiento' => $request->fecha_nacimiento,
                'rol' => $request->rol,
                'estado' => $request->rol === 'dueno' ? 'pendiente' : 'activo',
            ]);

            $token = $usuario->createToken('mobile-app')->plainTextToken;

            return response()->json([
                'success' => true,
                'message' => 'Usuario registrado exitosamente',
                'data' => ['token' => $token, 'user' => $usuario]
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al registrar usuario: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Iniciar sesión - POST /api/auth/login
     */
    public function login(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'email' => 'required|email',
                'password' => 'required',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'errors' => $validator->errors()
                ], 422);
            }

            $usuario = Usuario::where('email', $request->email)->first();

            if (!$usuario || !Hash::check($request->password, $usuario->password)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Credenciales incorrectas'
                ], 401);
            }

            // Verificar estado baneado primero
            if ($usuario->estaBaneado()) {
                $baneo = $usuario->baneos()->activos()->first();
                
                $diasRestantes = null;
                $isPermanent = false;
                
                if ($baneo->tipo_baneo === 'PERMANENT') {
                    $isPermanent = true;
                } elseif ($baneo->expira_en) {
                    $diasRestantes = Carbon::now()->diffInDays($baneo->expira_en, false);
                    if ($diasRestantes < 0) $diasRestantes = 0;
                }
                
                return response()->json([
                    'success' => false,
                    'message' => 'Tu cuenta está suspendida',
                    'code' => 'ACCOUNT_BANNED',
                    'baneo' => [
                        'tipo' => $baneo->tipo_baneo,
                        'razon' => $baneo->razon,
                        'expira_en' => $baneo->expira_en,
                        'dias_restantes' => $diasRestantes,
                        'is_permanent' => $isPermanent,
                    ]
                ], 403);
            }

            // Verificar estado pendiente (solo para dueños)
            if ($usuario->estado === 'pendiente') {
                $tieneCanchas = false;
                
                // Si es dueño, verificar si tiene canchas registradas
                if ($usuario->rol === 'dueno') {
                    $tieneCanchas = \App\Models\Cancha::where('dueno_id', $usuario->id)->exists();
                }
                
                return response()->json([
                    'success' => false,
                    'message' => 'Tu cuenta está pendiente de aprobación',
                    'code' => 'ACCOUNT_PENDING',
                    'data' => [
                        'user_id' => $usuario->id,
                        'email' => $usuario->email,
                        'rol' => $usuario->rol,
                        'tiene_canchas' => $tieneCanchas,
                    ]
                ], 403);
            }

            // Verificar estado rechazado
            if ($usuario->estado === 'rechazado') {
                return response()->json([
                    'success' => false,
                    'message' => 'Tu solicitud de registro fue rechazada',
                    'code' => 'ACCOUNT_REJECTED',
                ], 403);
            }

            // Verificar estado inactivo
            if ($usuario->estado === 'inactivo') {
                return response()->json([
                    'success' => false,
                    'message' => 'Tu cuenta está inactiva',
                    'code' => 'ACCOUNT_INACTIVE',
                ], 403);
            }

            // Solo permitir login si está activo
            if ($usuario->estado !== 'activo') {
                return response()->json([
                    'success' => false,
                    'message' => 'Tu cuenta no está activa'
                ], 403);
            }

            $token = $usuario->createToken('mobile-app')->plainTextToken;

            return response()->json([
                'success' => true,
                'data' => ['token' => $token, 'user' => $usuario]
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al iniciar sesión: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener perfil - GET /api/auth/profile
     */
    public function profile(Request $request)
    {
        return response()->json([
            'success' => true,
            'data' => $request->user()
        ]);
    }

    /**
     * Actualizar perfil - PUT /api/auth/profile
     */
    public function updateProfile(Request $request)
    {
        try {
            $usuario = $request->user();
            
            // Validar datos
            $validated = $request->validate([
                'nombre' => 'sometimes|string|max:100',
                'apellido' => 'sometimes|string|max:100',
                'telefono' => 'sometimes|string|max:20',
                'fecha_nacimiento' => 'sometimes|date',
                'foto_perfil' => 'sometimes|string|nullable',
            ]);
            
            // Actualizar campos básicos
            $usuario->update($request->only(['nombre', 'apellido', 'telefono', 'fecha_nacimiento', 'foto_perfil']));

            // Si se proporciona una nueva contraseña, actualizarla
            if ($request->filled('password')) {
                $usuario->password = bcrypt($request->password);
                $usuario->save();
            }

            return response()->json([
                'success' => true,
                'data' => $usuario
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar perfil: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Cerrar sesión - POST /api/auth/logout
     */
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'success' => true,
            'message' => 'Sesión cerrada correctamente'
        ]);
    }

    /**
     * Verificar token - POST /api/auth/verify-token
     */
    public function verifyToken(Request $request)
    {
        return response()->json([
            'success' => true,
            'message' => 'Token válido',
            'user' => $request->user()
        ]);
    }
}
