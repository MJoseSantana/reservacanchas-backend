<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Reservacion extends Model
{
    use HasFactory;

    protected $table = 'reservaciones';

    protected $fillable = [
        'cancha_id',
        'usuario_id',
        'fecha',
        'hora_inicio',
        'hora_fin',
        'duracion_horas',
        'precio_por_hora',
        'descuento_porcentaje',
        'precio_total',
        'tipo_reserva',
        'semanas_reservadas',
        'estado',
        'metodo_pago',
        'notas_cliente',
        'notas_dueno',
        'razon_cancelacion',
        'cancelada_en',
    ];

    protected $casts = [
        'fecha' => 'date',
        'duracion_horas' => 'float',
        'precio_por_hora' => 'float',
        'precio_total' => 'float',
        'descuento_porcentaje' => 'integer',
        'semanas_reservadas' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'cancelada_en' => 'datetime',
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

    // Scopes
    public function scopePendientes($query)
    {
        return $query->where('estado', 'pendiente');
    }

    public function scopeConfirmadas($query)
    {
        return $query->where('estado', 'confirmada');
    }

    public function scopeCompletadas($query)
    {
        return $query->where('estado', 'completada');
    }

    public function scopeCanceladas($query)
    {
        return $query->where('estado', 'cancelada');
    }

    public function scopePorFecha($query, $fecha)
    {
        return $query->whereDate('fecha', $fecha);
    }

    public function scopeFuturas($query)
    {
        return $query->where('fecha', '>=', now()->format('Y-m-d'));
    }

    public function scopePasadas($query)
    {
        return $query->where('fecha', '<', now()->format('Y-m-d'));
    }

    // Métodos auxiliares
    public function calcularDuracion()
    {
        $inicio = strtotime($this->hora_inicio);
        $fin = strtotime($this->hora_fin);
        return round(($fin - $inicio) / 3600, 2);
    }

    public function calcularPrecioTotal()
    {
        $this->duracion_horas = $this->calcularDuracion();
        $precioSinDescuento = (float) $this->precio_por_hora * (float) $this->duracion_horas;
        $this->precio_total = $precioSinDescuento * (1 - ($this->descuento_porcentaje / 100));
        return $this->precio_total;
    }

    public function puedeCancelar()
    {
        return in_array($this->estado, ['pendiente', 'confirmada']) 
            && $this->fecha >= now()->format('Y-m-d');
    }

    public function puedeConfirmar()
    {
        return $this->estado === 'pendiente';
    }

    public function puedeRechazar()
    {
        return $this->estado === 'pendiente';
    }

    // Events
    protected static function booted()
    {
        static::creating(function ($reservacion) {
            $reservacion->calcularPrecioTotal();
        });

        static::updating(function ($reservacion) {
            if ($reservacion->isDirty(['hora_inicio', 'hora_fin', 'precio_por_hora', 'descuento_porcentaje'])) {
                $reservacion->calcularPrecioTotal();
            }
        });
    }
}
