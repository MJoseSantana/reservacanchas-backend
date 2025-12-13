<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('preguntas_seguridad', function (Blueprint $table) {
            $table->id();
            $table->foreignId('usuario_id')->constrained('usuarios')->onDelete('cascade');
            $table->string('pregunta', 255);
            $table->string('respuesta', 255);
            $table->integer('orden')->default(1);
            $table->timestamps();

            $table->index('usuario_id');
            $table->unique(['usuario_id', 'orden']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('preguntas_seguridad');
    }
};
