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
        Schema::table('canchas', function (Blueprint $table) {
            // Nuevos campos de ubicación
            $table->string('distrito', 100)->nullable()->after('ciudad');
            $table->string('pais', 100)->nullable()->after('provincia');
            
            // Aforo de espectadores
            $table->integer('aforo_espectadores')->nullable()->after('jugadores_maximos');
            
            // Horarios por día (JSON con estructura por cada día de la semana)
            $table->json('horarios_por_dia')->nullable()->after('hora_cierre');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('canchas', function (Blueprint $table) {
            $table->dropColumn(['distrito', 'pais', 'aforo_espectadores', 'horarios_por_dia']);
        });
    }
};
