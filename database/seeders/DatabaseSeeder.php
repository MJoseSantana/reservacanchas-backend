<?php

namespace Database\Seeders;

use App\Models\Usuario;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Usuario Administrador
        Usuario::create([
            'nombre' => 'Admin',
            'apellido' => 'Sistema',
            'email' => 'admin@reservas.com',
            'password' => Hash::make('admin123'),
            'telefono' => '099111111',
            'rol' => 'admin',
            'estado' => 'activo',
        ]);

        // Usuario Dueño de Cancha
        Usuario::create([
            'nombre' => 'Carlos',
            'apellido' => 'Pérez',
            'email' => 'dueno@reservas.com',
            'password' => Hash::make('dueno123'),
            'telefono' => '099222222',
            'rol' => 'dueno',
            'estado' => 'activo',
        ]);

        // Usuario Jugador
        Usuario::create([
            'nombre' => 'Juan',
            'apellido' => 'González',
            'email' => 'jugador@reservas.com',
            'password' => Hash::make('jugador123'),
            'telefono' => '099333333',
            'rol' => 'jugador',
            'estado' => 'activo',
        ]);
    }
}
