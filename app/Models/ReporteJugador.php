<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReporteJugador extends Model
{
    use HasFactory;

    protected $table = 'reportes_jugadores';

    protected $fillable = [
        'jugador_id',
        'dueno_id',
        'reserva_id',
        'tipo_reporte',
        'descripcion',
        'estado',
        'admin_id',
        'accion_tomada',
        'fecha_revision',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'fecha_revision' => 'datetime',
    ];

    /**
     * Relación con el jugador reportado
     */
    public function jugador()
    {
        return $this->belongsTo(Usuario::class, 'jugador_id');
    }

    /**
     * Relación con el dueño que reportó
     */
    public function dueno()
    {
        return $this->belongsTo(Usuario::class, 'dueno_id');
    }

    /**
     * Relación con la reserva asociada
     */
    public function reserva()
    {
        return $this->belongsTo(Reservacion::class, 'reserva_id');
    }

    /**
     * Relación con el admin que revisó
     */
    public function admin()
    {
        return $this->belongsTo(Usuario::class, 'admin_id');
    }
}
