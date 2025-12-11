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
        Schema::create('baneos', function (Blueprint $table) {
            $table->id();
            
            // Puede banear a un usuario O a una cancha
            $table->foreignId('usuario_id')->nullable()->constrained('usuarios')->onDelete('cascade');
            $table->foreignId('cancha_id')->nullable()->constrained('canchas')->onDelete('cascade');
            
            $table->enum('tipo_baneo', ['PERMANENT', 'TEMPORARY']);
            $table->text('razon');
            $table->integer('duracion_dias')->nullable();
            
            // Admin que aplicó el baneo
            $table->foreignId('admin_id')->constrained('usuarios')->onDelete('cascade');
            $table->text('notas_admin')->nullable();
            
            $table->boolean('activo')->default(true);
            
            $table->timestamps();
            $table->timestamp('expira_en')->nullable();
            $table->timestamp('levantado_en')->nullable();
            $table->foreignId('levantado_por_id')->nullable()->constrained('usuarios')->onDelete('set null');
            
            // Índices
            $table->index('usuario_id');
            $table->index('cancha_id');
            $table->index('activo');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('baneos');
    }
};
