<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Cancha extends Model
{
    use HasFactory;

    protected $table = 'canchas';

    protected $fillable = [
        'dueno_id',
        'nombre',
        'descripcion',
        'direccion',
        'distrito',
        'provincia',
        'pais',
        'ciudad',
        'referencia',
        'latitud',
        'longitud',
        'tipo_deporte',
        'tipo_superficie',
        'jugadores_maximos',
        'aforo_espectadores',
        'precio_diurno',
        'precio_nocturno',
        'hora_inicio_nocturno',
        'hora_apertura',
        'hora_cierre',
        'horarios_por_dia',
        'descuentos',
        'servicios',
        'servicios_otros',
        'imagenes',
        'estado',
        'calificacion_promedio',
        'numero_calificaciones',
    ];

    protected $casts = [
        'latitud' => 'decimal:8',
        'longitud' => 'decimal:8',
        'precio_diurno' => 'decimal:2',
        'precio_nocturno' => 'decimal:2',
        'hora_inicio_nocturno' => 'datetime:H:i',
        'hora_apertura' => 'datetime:H:i',
        'hora_cierre' => 'datetime:H:i',
        'descuentos' => 'array',
        'servicios' => 'array',
        'imagenes' => 'array',
        'horarios_por_dia' => 'array',
        'calificacion_promedio' => 'decimal:2',
        'jugadores_maximos' => 'integer',
        'aforo_espectadores' => 'integer',
        'numero_calificaciones' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected $appends = ['imagenes_absolutas'];

    /**
     * Accessor para convertir URLs relativas de imágenes a absolutas
     */
    public function getImagenesAbsolutasAttribute()
    {
        if (!$this->imagenes || !is_array($this->imagenes)) {
            return [];
        }

        return array_map(function($url) {
            // Si ya es URL absoluta, retornar como está
            if (str_starts_with($url, 'http://') || str_starts_with($url, 'https://')) {
                return $url;
            }
            
            // Si es relativa, convertir a absoluta
            return url($url);
        }, $this->imagenes);
    }

    // Relaciones
    public function dueno()
    {
        return $this->belongsTo(Usuario::class, 'dueno_id');
    }

    public function reservaciones()
    {
        return $this->hasMany(Reservacion::class, 'cancha_id');
    }

    public function usuariosFavoritos()
    {
        return $this->belongsToMany(Usuario::class, 'favoritos', 'cancha_id', 'usuario_id')
            ->withTimestamps();
    }

    public function calificaciones()
    {
        return $this->hasMany(CalificacionCancha::class, 'cancha_id');
    }

    public function reportes()
    {
        return $this->hasMany(ReporteCancha::class, 'cancha_id');
    }

    public function baneos()
    {
        return $this->hasMany(Baneo::class, 'cancha_id');
    }

    // Scopes
    public function scopeActivas($query)
    {
        return $query->where('estado', 'activa');
    }

    public function scopePorCiudad($query, $ciudad)
    {
        return $query->where('ciudad', $ciudad);
    }

    public function scopePorTipoDeporte($query, $tipo)
    {
        return $query->where('tipo_deporte', $tipo);
    }

    public function scopeConCalificacionMinima($query, $calificacion)
    {
        return $query->where('calificacion_promedio', '>=', $calificacion);
    }

    public function scopeBuscar($query, $termino)
    {
        return $query->whereRaw("to_tsvector('spanish', nombre || ' ' || COALESCE(descripcion, '')) @@ plainto_tsquery('spanish', ?)", [$termino]);
    }

    // Métodos auxiliares
    public function calcularPrecioPorHora($hora)
    {
        $horaInicio = strtotime($this->hora_inicio_nocturno);
        $horaConsulta = strtotime($hora);
        
        return $horaConsulta >= $horaInicio ? $this->precio_nocturno : $this->precio_diurno;
    }

    public function aplicarDescuento($semanas)
    {
        if (!$this->descuentos) {
            return 0;
        }

        if ($semanas >= 3 && isset($this->descuentos['tresSemanasOMas'])) {
            return $this->descuentos['tresSemanasOMas'];
        } elseif ($semanas == 2 && isset($this->descuentos['dosSemanas'])) {
            return $this->descuentos['dosSemanas'];
        } elseif ($semanas == 1 && isset($this->descuentos['unaSemana'])) {
            return $this->descuentos['unaSemana'];
        }

        return 0;
    }

    public function estaDisponible($fecha, $horaInicio, $horaFin)
    {
        return !$this->reservaciones()
            ->where('fecha', $fecha)
            ->whereIn('estado', ['pendiente', 'confirmada'])
            ->where(function ($query) use ($horaInicio, $horaFin) {
                $query->where(function ($q) use ($horaInicio, $horaFin) {
                    $q->where('hora_inicio', '<=', $horaInicio)
                      ->where('hora_fin', '>', $horaInicio);
                })->orWhere(function ($q) use ($horaInicio, $horaFin) {
                    $q->where('hora_inicio', '<', $horaFin)
                      ->where('hora_fin', '>=', $horaFin);
                })->orWhere(function ($q) use ($horaInicio, $horaFin) {
                    $q->where('hora_inicio', '>=', $horaInicio)
                      ->where('hora_fin', '<=', $horaFin);
                });
            })
            ->exists();
    }

    public function estaBaneada()
    {
        return $this->baneos()
            ->where('activo', true)
            ->where(function ($query) {
                $query->where('tipo_baneo', 'PERMANENT')
                    ->orWhere(function ($q) {
                        $q->where('tipo_baneo', 'TEMPORARY')
                            ->where('expira_en', '>', now());
                    });
            })
            ->exists();
    }

    public function actualizarCalificacion()
    {
        $this->calificacion_promedio = $this->calificaciones()->avg('calificacion') ?? 0;
        $this->numero_calificaciones = $this->calificaciones()->count();
        $this->save();
    }
}
