<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Baneo extends Model
{
    use HasFactory;

    protected $table = 'baneos';

    protected $fillable = [
        'usuario_id',
        'cancha_id',
        'tipo_baneo',
        'razon',
        'duracion_dias',
        'admin_id',
        'notas_admin',
        'activo',
        'expira_en',
        'levantado_en',
        'levantado_por_id',
    ];

    protected $casts = [
        'duracion_dias' => 'integer',
        'activo' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'expira_en' => 'datetime',
        'levantado_en' => 'datetime',
    ];

    // Relaciones
    public function usuario()
    {
        return $this->belongsTo(Usuario::class, 'usuario_id');
    }

    public function cancha()
    {
        return $this->belongsTo(Cancha::class, 'cancha_id');
    }

    public function admin()
    {
        return $this->belongsTo(Usuario::class, 'admin_id');
    }

    public function levantadoPor()
    {
        return $this->belongsTo(Usuario::class, 'levantado_por_id');
    }

    // Scopes
    public function scopeActivos($query)
    {
        return $query->where('activo', true);
    }

    public function scopeVigentes($query)
    {
        return $query->where('activo', true)
            ->where(function ($q) {
                $q->where('tipo_baneo', 'PERMANENT')
                  ->orWhere(function ($subq) {
                      $subq->where('tipo_baneo', 'TEMPORARY')
                          ->where('expira_en', '>', now());
                  });
            });
    }

    public function scopeExpirados($query)
    {
        return $query->where('activo', true)
            ->where('tipo_baneo', 'TEMPORARY')
            ->where('expira_en', '<=', now());
    }

    // Métodos auxiliares
    public function estaVigente()
    {
        if (!$this->activo) {
            return false;
        }

        if ($this->tipo_baneo === 'PERMANENT') {
            return true;
        }

        return $this->expira_en && $this->expira_en->isFuture();
    }

    public function levantar($adminId)
    {
        $this->activo = false;
        $this->levantado_en = now();
        $this->levantado_por_id = $adminId;
        $this->save();
    }

    // Events
    protected static function booted()
    {
        static::creating(function ($baneo) {
            if ($baneo->tipo_baneo === 'TEMPORARY' && $baneo->duracion_dias) {
                $baneo->expira_en = Carbon::now()->addDays($baneo->duracion_dias);
            }
        });
    }
}
