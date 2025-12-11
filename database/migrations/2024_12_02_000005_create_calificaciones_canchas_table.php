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
        Schema::create('calificaciones_canchas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cancha_id')->constrained('canchas')->onDelete('cascade');
            $table->foreignId('usuario_id')->constrained('usuarios')->onDelete('cascade');
            $table->integer('calificacion')->unsigned()->comment('1 a 5 estrellas');
            $table->text('comentario')->nullable();
            $table->timestamps();
            
            // Un usuario solo puede calificar una vez cada cancha
            $table->unique(['cancha_id', 'usuario_id']);
            
            // Índices
            $table->index('cancha_id');
            $table->index('usuario_id');
        });
        
        // Check constraint usando DB::statement para PostgreSQL
        DB::statement('ALTER TABLE calificaciones_canchas ADD CONSTRAINT calificaciones_canchas_calificacion_check CHECK (calificacion >= 1 AND calificacion <= 5)');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('calificaciones_canchas');
    }
};
