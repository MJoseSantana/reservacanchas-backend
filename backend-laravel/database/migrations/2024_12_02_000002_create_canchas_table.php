<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('canchas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dueno_id')->constrained('usuarios')->onDelete('cascade');
            
            // Información básica
            $table->string('nombre', 255);
            $table->text('descripcion')->nullable();
            
            // Ubicación
            $table->text('direccion');
            $table->string('provincia', 100)->nullable();
            $table->string('ciudad', 100);
            $table->text('referencia')->nullable();
            $table->decimal('latitud', 10, 8)->nullable();
            $table->decimal('longitud', 11, 8)->nullable();
            
            // Características
            $table->string('tipo_deporte', 50)->default('Fútbol');
            $table->string('tipo_superficie', 50)->nullable();
            $table->integer('jugadores_maximos')->default(22);
            
            // Precios
            $table->decimal('precio_diurno', 10, 2)->default(0);
            $table->decimal('precio_nocturno', 10, 2)->default(0);
            $table->time('hora_inicio_nocturno')->default('18:00');
            
            // Horarios de atención
            $table->time('hora_apertura')->default('06:00');
            $table->time('hora_cierre')->default('23:00');
            
            // Descuentos por temporada (JSON)
            $table->json('descuentos')->nullable();
            
            // Servicios (Array PostgreSQL)
            $table->json('servicios')->nullable();
            $table->text('servicios_otros')->nullable();
            
            // Imágenes (Array de URLs)
            $table->json('imagenes')->nullable();
            
            // Estado y calificación
            $table->enum('estado', ['activa', 'inactiva', 'en_revision', 'bloqueada'])->default('activa');
            $table->decimal('calificacion_promedio', 3, 2)->default(0);
            $table->integer('numero_calificaciones')->default(0);
            
            $table->timestamps();
            
            // Índices
            $table->index('dueno_id');
            $table->index('ciudad');
            $table->index('tipo_deporte');
            $table->index('estado');
            $table->index('calificacion_promedio');
            
            // Índice full-text para búsquedas
            $table->fullText(['nombre', 'descripcion']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('canchas');
    }
};
