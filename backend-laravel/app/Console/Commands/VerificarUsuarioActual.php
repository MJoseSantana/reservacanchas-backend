<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Usuario;
use App\Models\Cancha;

class VerificarUsuarioActual extends Command
{
    protected $signature = 'verificar:usuario {email}';
    protected $description = 'Verifica las canchas de un usuario específico';

    public function handle()
    {
        $email = $this->argument('email');
        
        $usuario = Usuario::where('email', $email)->first();
        
        if (!$usuario) {
            $this->error("Usuario no encontrado: {$email}");
            return 1;
        }
        
        $this->info("=== DATOS DEL USUARIO ===");
        $this->info("ID: {$usuario->id}");
        $this->info("Email: {$usuario->email}");
        $this->info("Nombre: {$usuario->nombre}");
        $this->info("Rol: {$usuario->rol}");
        
        $canchas = Cancha::where('dueno_id', $usuario->id)->get();
        
        $this->info("\n=== CANCHAS DEL USUARIO ===");
        $this->info("Total: " . $canchas->count());
        
        foreach ($canchas as $cancha) {
            $this->info("\nCancha ID: {$cancha->id}");
            $this->info("  Nombre: {$cancha->nombre}");
            $this->info("  Tipo: {$cancha->tipo_cancha}");
            $this->info("  Estado: {$cancha->estado}");
            $this->info("  Dueño ID: {$cancha->dueno_id}");
        }
        
        return 0;
    }
}
