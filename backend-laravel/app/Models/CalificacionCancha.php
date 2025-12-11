<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CalificacionCancha extends Model
{
    use HasFactory;

    protected $table = 'calificaciones_canchas';

    protected $fillable = [
        'cancha_id',
        'usuario_id',
        'calificacion',
        'comentario',
    ];

    protected $casts = [
        'calificacion' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Relaciones
    public function cancha()
    {
        return $this->belongsTo(Cancha::class, 'cancha_id');
    }

    public function usuario()
    {
        return $this->belongsTo(Usuario::class, 'usuario_id');
    }

    // Events
    protected static function booted()
    {
        static::created(function ($calificacion) {
            $calificacion->cancha->actualizarCalificacion();
        });

        static::updated(function ($calificacion) {
            $calificacion->cancha->actualizarCalificacion();
        });

        static::deleted(function ($calificacion) {
            $calificacion->cancha->actualizarCalificacion();
        });
    }
}
