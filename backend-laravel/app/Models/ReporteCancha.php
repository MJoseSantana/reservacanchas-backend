<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReporteCancha extends Model
{
    use HasFactory;

    protected $table = 'reportes_canchas';

    protected $fillable = [
        'cancha_id',
        'usuario_reportante_id',
        'razon',
        'descripcion',
        'estado',
        'revisado_por_id',
        'notas_revision',
        'accion_tomada',
        'revisado_en',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'revisado_en' => 'datetime',
    ];

    // Relaciones
    public function cancha()
    {
        return $this->belongsTo(Cancha::class, 'cancha_id');
    }

    public function usuarioReportante()
    {
        return $this->belongsTo(Usuario::class, 'usuario_reportante_id');
    }

    public function revisadoPor()
    {
        return $this->belongsTo(Usuario::class, 'revisado_por_id');
    }

    // Scopes
    public function scopePendientes($query)
    {
        return $query->where('estado', 'pendiente');
    }

    public function scopeRevisados($query)
    {
        return $query->whereIn('estado', ['revisado', 'resuelto', 'rechazado']);
    }
}
