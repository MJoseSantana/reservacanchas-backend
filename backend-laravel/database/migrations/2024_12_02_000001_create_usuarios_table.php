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
        Schema::create('usuarios', function (Blueprint $table) {
            $table->id();
            $table->string('email')->unique();
            $table->string('password');
            $table->string('nombre', 100);
            $table->string('apellido', 100);
            $table->string('telefono', 20);
            $table->date('fecha_nacimiento')->nullable();
            $table->enum('rol', ['jugador', 'dueno', 'admin'])->default('jugador');
            $table->enum('estado', ['activo', 'inactivo', 'baneado', 'pendiente', 'rechazado'])->default('activo');
            $table->string('foto_perfil')->nullable();
            $table->boolean('email_verificado')->default(false);
            $table->timestamps();
            
            // Índices
            $table->index('email');
            $table->index('rol');
            $table->index('estado');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('usuarios');
    }
};
