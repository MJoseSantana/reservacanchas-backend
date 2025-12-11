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
        Schema::create('reservaciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cancha_id')->constrained('canchas')->onDelete('cascade');
            $table->foreignId('usuario_id')->constrained('usuarios')->onDelete('cascade');
            
            // Datos temporales
            $table->date('fecha');
            $table->time('hora_inicio');
            $table->time('hora_fin');
            $table->decimal('duracion_horas', 4, 2);
            
            // Precios
            $table->decimal('precio_por_hora', 10, 2);
            $table->integer('descuento_porcentaje')->default(0);
            $table->decimal('precio_total', 10, 2);
            
            // Tipo de reserva
            $table->enum('tipo_reserva', ['individual', 'temporal_semanal', 'temporal_mensual'])->default('individual');
            $table->integer('semanas_reservadas')->default(1);
            
            // Estado
            $table->enum('estado', ['pendiente', 'confirmada', 'completada', 'cancelada', 'rechazada'])->default('pendiente');
            $table->string('metodo_pago', 50)->nullable();
            
            // Notas
            $table->text('notas_cliente')->nullable();
            $table->text('notas_dueno')->nullable();
            $table->text('razon_cancelacion')->nullable();
            
            $table->timestamps();
            $table->timestamp('cancelada_en')->nullable();
            
            // Índices
            $table->index('cancha_id');
            $table->index('usuario_id');
            $table->index('fecha');
            $table->index('estado');
            $table->index(['cancha_id', 'fecha']);
            $table->index(['cancha_id', 'estado', 'fecha']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reservaciones');
    }
};
