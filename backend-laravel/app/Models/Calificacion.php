<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Calificacion extends Model
{
    use HasFactory;

    protected $table = 'calificaciones_canchas';

    protected $fillable = [
        'usuario_id',
        'cancha_id',
        'calificacion',
        'comentario',
    ];

    protected $casts = [
        'calificacion' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Relación con Usuario
     */
    public function usuario()
    {
        return $this->belongsTo(Usuario::class, 'usuario_id');
    }

    /**
     * Relación con Cancha
     */
    public function cancha()
    {
        return $this->belongsTo(Cancha::class, 'cancha_id');
    }
}
