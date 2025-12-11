<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Usuario;
use App\Models\Cancha;

class CrearCanchaParaDueno extends Command
{
    protected $signature = 'crear:cancha-dueno {email}';
    protected $description = 'Crear una cancha de ejemplo para un dueño específico';

    public function handle()
    {
        $email = $this->argument('email');
        
        $dueno = Usuario::where('email', $email)->where('rol', 'dueno')->first();
        
        if (!$dueno) {
            $this->error("No se encontró un usuario dueño con email: $email");
            return 1;
        }
        
        $this->info("Creando cancha para: {$dueno->nombre} ({$dueno->email})");
        
        $cancha = Cancha::create([
            'dueno_id' => $dueno->id,
            'nombre' => 'Cancha de ' . $dueno->nombre,
            'descripcion' => 'Cancha creada automáticamente por el sistema',
            'direccion' => 'Dirección de ejemplo',
            'ciudad' => 'Lima',
            'provincia' => 'Lima',
            'pais' => 'Perú',
            'tipo_deporte' => 'Fútbol',
            'tipo_superficie' => 'Césped Sintético',
            'jugadores_maximos' => 11,
            'precio_diurno' => 50.00,
            'precio_nocturno' => 70.00,
            'hora_inicio_nocturno' => '18:00',
            'estado' => 'activa',
            'imagenes' => [],
            'servicios' => [],
            'descuentos' => [],
        ]);
        
        $this->info("✅ Cancha creada exitosamente:");
        $this->line("   ID: {$cancha->id}");
        $this->line("   Nombre: {$cancha->nombre}");
        $this->line("   Estado: {$cancha->estado}");
        
        return 0;
    }
}
