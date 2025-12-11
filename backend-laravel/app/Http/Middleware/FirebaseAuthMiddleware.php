<?php

namespace App\Http\Middleware;

use App\Services\FirebaseService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Kreait\Firebase\Exception\Auth\FailedToVerifyToken;

class FirebaseAuthMiddleware
{
    protected $firebaseService;

    public function __construct(FirebaseService $firebaseService)
    {
        $this->firebaseService = $firebaseService;
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->bearerToken();

        if (!$token) {
            return response()->json([
                'success' => false,
                'message' => 'Token de autenticación no proporcionado'
            ], 401);
        }

        try {
            // Verificar el token con Firebase
            $verifiedIdToken = $this->firebaseService->verifyIdToken($token);

            if (!$verifiedIdToken) {
                return response()->json([
                    'success' => false,
                    'message' => 'Token inválido'
                ], 401);
            }

            // Obtener el UID del usuario
            $uid = $verifiedIdToken->claims()->get('sub');
            
            // Agregar el UID a la request para usarlo en los controladores
            $request->merge(['user_uid' => $uid]);

            return $next($request);

        } catch (FailedToVerifyToken $e) {
            return response()->json([
                'success' => false,
                'message' => 'Token inválido o expirado'
            ], 401);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al verificar autenticación: ' . $e->getMessage()
            ], 500);
        }
    }
}
