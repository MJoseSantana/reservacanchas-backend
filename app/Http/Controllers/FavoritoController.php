<?php

namespace App\Http\Controllers;

use App\Models\Favorito;
use App\Models\Cancha;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class FavoritoController extends Controller
{
    /**
     * Obtener favoritos del usuario autenticado
     * GET /api/favoritos
     */
    public function index(Request $request)
    {
        try {
            $user = $request->user();

            $favoritos = Favorito::with('cancha')
                ->where('usuario_id', $user->id)
                ->get();

            return response()->json([
                'success' => true,
                'data' => $favoritos
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener favoritos: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Agregar cancha a favoritos
     * POST /api/favoritos
     */
    public function store(Request $request)
    {
        try {
            $user = $request->user();

            $validator = Validator::make($request->all(), [
                'cancha_id' => 'required|exists:canchas,id',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Errores de validación',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Verificar si ya existe
            $existe = Favorito::where('usuario_id', $user->id)
                ->where('cancha_id', $request->cancha_id)
                ->exists();

            if ($existe) {
                return response()->json([
                    'success' => false,
                    'message' => 'La cancha ya está en favoritos'
                ], 409);
            }

            $favorito = Favorito::create([
                'usuario_id' => $user->id,
                'cancha_id' => $request->cancha_id,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Cancha agregada a favoritos',
                'data' => $favorito->load('cancha')
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al agregar favorito: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Eliminar cancha de favoritos
     * DELETE /api/favoritos/{cancha_id}
     */
    public function destroy(Request $request, $canchaId)
    {
        try {
            $user = $request->user();

            $favorito = Favorito::where('usuario_id', $user->id)
                ->where('cancha_id', $canchaId)
                ->first();

            if (!$favorito) {
                return response()->json([
                    'success' => false,
                    'message' => 'Favorito no encontrado'
                ], 404);
            }

            $favorito->delete();

            return response()->json([
                'success' => true,
                'message' => 'Cancha eliminada de favoritos'
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar favorito: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Verificar si una cancha está en favoritos
     * GET /api/favoritos/verificar/{cancha_id}
     */
    public function verificar(Request $request, $canchaId)
    {
        try {
            $user = $request->user();

            $esFavorito = Favorito::where('usuario_id', $user->id)
                ->where('cancha_id', $canchaId)
                ->exists();

            return response()->json([
                'success' => true,
                'es_favorito' => $esFavorito
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al verificar favorito: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Verificar si una cancha está en favoritos (alias)
     * GET /api/favoritos/check/{cancha_id}
     */
    public function check(Request $request, $canchaId)
    {
        try {
            $user = $request->user();

            $favorito = Favorito::where('usuario_id', $user->id)
                ->where('cancha_id', $canchaId)
                ->first();

            return response()->json([
                'success' => true,
                'es_favorito' => $favorito ? true : false,
                'favorito_id' => $favorito ? $favorito->id : null
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al verificar favorito: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Alternar favorito (agregar si no existe, eliminar si existe)
     * POST /api/favoritos/toggle
     */
    public function toggle(Request $request)
    {
        try {
            $user = $request->user();

            $validator = Validator::make($request->all(), [
                'cancha_id' => 'required|exists:canchas,id',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Errores de validación',
                    'errors' => $validator->errors()
                ], 422);
            }

            $favorito = Favorito::where('usuario_id', $user->id)
                ->where('cancha_id', $request->cancha_id)
                ->first();

            if ($favorito) {
                // Ya existe, eliminar
                $favorito->delete();
                return response()->json([
                    'success' => true,
                    'es_favorito' => false,
                    'message' => 'Cancha eliminada de favoritos'
                ], 200);
            } else {
                // No existe, crear
                $nuevoFavorito = Favorito::create([
                    'usuario_id' => $user->id,
                    'cancha_id' => $request->cancha_id,
                ]);

                return response()->json([
                    'success' => true,
                    'es_favorito' => true,
                    'message' => 'Cancha agregada a favoritos',
                    'data' => $nuevoFavorito
                ], 201);
            }

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al alternar favorito: ' . $e->getMessage()
            ], 500);
        }
    }
}
