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
        Schema::create('reportes_canchas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cancha_id')->constrained('canchas')->onDelete('cascade');
            $table->foreignId('usuario_reportante_id')->constrained('usuarios')->onDelete('cascade');
            
            $table->string('razon', 100);
            $table->text('descripcion')->nullable();
            $table->enum('estado', ['pendiente', 'revisado', 'resuelto', 'rechazado'])->default('pendiente');
            
            // Revisión por admin
            $table->foreignId('revisado_por_id')->nullable()->constrained('usuarios')->onDelete('set null');
            $table->text('notas_revision')->nullable();
            $table->text('accion_tomada')->nullable();
            
            $table->timestamps();
            $table->timestamp('revisado_en')->nullable();
            
            // Índices
            $table->index('cancha_id');
            $table->index('usuario_reportante_id');
            $table->index('estado');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reportes_canchas');
    }
};
