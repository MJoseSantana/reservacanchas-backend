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
        Schema::create('reportes_jugadores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('jugador_id')->constrained('usuarios')->onDelete('cascade');
            $table->foreignId('dueno_id')->constrained('usuarios')->onDelete('cascade');
            $table->foreignId('reserva_id')->nullable()->constrained('reservaciones')->onDelete('set null');
            
            $table->string('tipo_reporte', 100);
            $table->text('descripcion');
            $table->enum('estado', ['pendiente', 'revisado', 'resuelto'])->default('pendiente');
            
            // Revisión por admin
            $table->foreignId('admin_id')->nullable()->constrained('usuarios')->onDelete('set null');
            $table->text('accion_tomada')->nullable();
            
            $table->timestamps();
            $table->timestamp('fecha_revision')->nullable();
            
            // Índices
            $table->index('jugador_id');
            $table->index('dueno_id');
            $table->index('estado');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reportes_jugadores');
    }
};
