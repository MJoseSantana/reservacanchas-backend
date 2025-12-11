<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Usuario;
use Illuminate\Support\Facades\Hash;

class GenerarTokenUsuario extends Command
{
    protected $signature = 'generar:token {email}';
    protected $description = 'Genera un token de acceso para un usuario';

    public function handle()
    {
        $email = $this->argument('email');
        
        $usuario = Usuario::where('email', $email)->first();
        
        if (!$usuario) {
            $this->error("Usuario no encontrado: {$email}");
            return 1;
        }
        
        // Eliminar tokens anteriores
        $usuario->tokens()->delete();
        
        // Crear nuevo token
        $token = $usuario->createToken('test-token')->plainTextToken;
        
        $this->info("=== TOKEN GENERADO ===");
        $this->info("Usuario: {$usuario->email}");
        $this->info("Token: {$token}");
        $this->info("\nPuedes usar este token para probar el endpoint:");
        $this->info("curl -H \"Authorization: Bearer {$token}\" http://localhost:8000/api/canchas/mis-canchas");
        
        return 0;
    }
}
