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
        Schema::create('tokens_fcm', function (Blueprint $table) {
            $table->id();
            $table->foreignId('usuario_id')->constrained('usuarios')->onDelete('cascade');
            
            $table->text('token')->unique();
            $table->string('dispositivo', 50)->nullable(); // 'android', 'ios', 'web'
            $table->boolean('activo')->default(true);
            
            $table->timestamps();
            $table->timestamp('ultimo_uso')->useCurrent();
            
            // Índices
            $table->index('usuario_id');
            $table->index('activo');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tokens_fcm');
    }
};
