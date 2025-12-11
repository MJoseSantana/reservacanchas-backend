<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Modificar el enum de estado para agregar 'pendiente' y 'rechazado'
        DB::statement("ALTER TABLE usuarios DROP CONSTRAINT IF EXISTS usuarios_estado_check");
        DB::statement("ALTER TABLE usuarios ALTER COLUMN estado TYPE VARCHAR(20)");
        DB::statement("ALTER TABLE usuarios ADD CONSTRAINT usuarios_estado_check CHECK (estado IN ('activo', 'inactivo', 'baneado', 'pendiente', 'rechazado'))");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Volver al estado anterior
        DB::statement("ALTER TABLE usuarios DROP CONSTRAINT IF EXISTS usuarios_estado_check");
        DB::statement("ALTER TABLE usuarios ALTER COLUMN estado TYPE VARCHAR(20)");
        DB::statement("ALTER TABLE usuarios ADD CONSTRAINT usuarios_estado_check CHECK (estado IN ('activo', 'inactivo', 'baneado'))");
    }
};
