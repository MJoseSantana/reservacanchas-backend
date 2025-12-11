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
        Schema::create('notificaciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('usuario_id')->constrained('usuarios')->onDelete('cascade');
            
            $table->string('tipo', 50); // 'nueva_reserva', 'cancelacion', 'recordatorio', etc.
            $table->string('titulo', 255);
            $table->text('mensaje');
            $table->json('datos_extra')->nullable(); // Información adicional específica del tipo
            
            $table->boolean('leida')->default(false);
            $table->timestamp('leida_en')->nullable();
            
            $table->timestamps();
            
            // Índices
            $table->index('usuario_id');
            $table->index(['usuario_id', 'leida']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notificaciones');
    }
};
