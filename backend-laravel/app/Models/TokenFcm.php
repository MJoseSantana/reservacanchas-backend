<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TokenFcm extends Model
{
    use HasFactory;

    protected $table = 'tokens_fcm';

    protected $fillable = [
        'usuario_id',
        'token',
        'dispositivo',
        'activo',
        'ultimo_uso',
    ];

    protected $casts = [
        'activo' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'ultimo_uso' => 'datetime',
    ];

    // Relaciones
    public function usuario()
    {
        return $this->belongsTo(Usuario::class, 'usuario_id');
    }

    // Scopes
    public function scopeActivos($query)
    {
        return $query->where('activo', true);
    }

    public function scopePorDispositivo($query, $dispositivo)
    {
        return $query->where('dispositivo', $dispositivo);
    }

    // Métodos auxiliares
    public function actualizarUso()
    {
        $this->ultimo_uso = now();
        $this->save();
    }

    public function desactivar()
    {
        $this->activo = false;
        $this->save();
    }
}
