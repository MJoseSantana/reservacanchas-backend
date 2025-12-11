<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Usuario;
use App\Models\Cancha;

class VerificarDatosDuenos extends Command
{
    protected $signature = 'verificar:duenos';
    protected $description = 'Verificar datos de usuarios dueños y sus canchas';

    public function handle()
    {
        $this->info('=== VERIFICACIÓN DE DUEÑOS Y CANCHAS ===');
        $this->newLine();

        // 1. Usuarios con rol dueno
        $this->info('1. USUARIOS CON ROL DUEÑO:');
        $duenos = Usuario::where('rol', 'dueno')->get();
        
        if ($duenos->isEmpty()) {
            $this->warn('   No hay usuarios con rol "dueno"');
        } else {
            $this->table(
                ['ID', 'Nombre', 'Email', 'Estado', 'Fecha Registro'],
                $duenos->map(fn($u) => [
                    $u->id,
                    $u->nombre . ' ' . $u->apellido,
                    $u->email,
                    $u->estado,
                    $u->created_at->format('Y-m-d H:i')
                ])
            );
        }
        
        $this->newLine();

        // 2. Canchas por dueño
        $this->info('2. CANCHAS POR DUEÑO:');
        foreach ($duenos as $dueno) {
            $canchas = Cancha::where('dueno_id', $dueno->id)->get();
            $this->line("   Dueño: {$dueno->nombre} ({$dueno->email})");
            $this->line("   Total canchas: " . $canchas->count());
            
            if ($canchas->isNotEmpty()) {
                foreach ($canchas as $cancha) {
                    $this->line("      - ID: {$cancha->id}, Nombre: {$cancha->nombre}, Estado: {$cancha->estado}");
                }
            }
            $this->newLine();
        }

        // 3. Todas las canchas
        $this->info('3. TODAS LAS CANCHAS EN LA BASE DE DATOS:');
        $todasCanchas = Cancha::with('dueno')->get();
        
        if ($todasCanchas->isEmpty()) {
            $this->warn('   No hay canchas registradas');
        } else {
            $this->table(
                ['ID', 'Nombre', 'Dueño ID', 'Dueño Email', 'Estado'],
                $todasCanchas->map(fn($c) => [
                    $c->id,
                    $c->nombre,
                    $c->dueno_id,
                    $c->dueno->email ?? 'SIN DUEÑO',
                    $c->estado
                ])
            );
        }

        // 4. Resumen
        $this->newLine();
        $this->info('=== RESUMEN ===');
        $this->line('Total usuarios dueños: ' . $duenos->count());
        $this->line('Total canchas: ' . $todasCanchas->count());
        $this->line('Canchas huérfanas: ' . Cancha::whereNull('dueno_id')->count());
        
        return 0;
    }
}
