<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\HasApiTokens;

class Usuario extends Authenticatable
{
    use HasFactory, Notifiable, HasApiTokens;

    protected $table = 'usuarios';

    protected $fillable = [
        'email',
        'password',
        'nombre',
        'apellido',
        'telefono',
        'fecha_nacimiento',
        'rol',
        'estado',
        'foto_perfil',
        'email_verificado',
    ];

    protected $hidden = [
        'password',
    ];

    protected $casts = [
        'fecha_nacimiento' => 'date',
        'email_verificado' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Accessor para nombre completo
    public function getNombreCompletoAttribute()
    {
        return "{$this->nombre} {$this->apellido}";
    }

    // Mutator para password
    public function setPasswordAttribute($value)
    {
        $this->attributes['password'] = Hash::make($value);
    }

    // Relaciones
    public function canchas()
    {
        return $this->hasMany(Cancha::class, 'dueno_id');
    }

    public function reservaciones()
    {
        return $this->hasMany(Reservacion::class, 'usuario_id');
    }

    public function favoritos()
    {
        return $this->belongsToMany(Cancha::class, 'favoritos', 'usuario_id', 'cancha_id')
            ->withTimestamps();
    }

    public function calificaciones()
    {
        return $this->hasMany(CalificacionCancha::class, 'usuario_id');
    }

    public function reportes()
    {
        return $this->hasMany(ReporteCancha::class, 'usuario_reportante_id');
    }

    public function baneos()
    {
        return $this->hasMany(Baneo::class, 'usuario_id');
    }

    public function notificaciones()
    {
        return $this->hasMany(Notificacion::class, 'usuario_id');
    }

    public function tokensFcm()
    {
        return $this->hasMany(TokenFcm::class, 'usuario_id');
    }

    // Scopes
    public function scopeActivos($query)
    {
        return $query->where('estado', 'activo');
    }

    public function scopeJugadores($query)
    {
        return $query->where('rol', 'jugador');
    }

    public function scopeDuenos($query)
    {
        return $query->where('rol', 'dueno');
    }

    public function scopeAdmins($query)
    {
        return $query->where('rol', 'admin');
    }

    // Métodos auxiliares
    public function estaBaneado()
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

    public function puedeCrearCanchas()
    {
        return $this->rol === 'dueno' && $this->estado === 'activo' && !$this->estaBaneado();
    }

    public function puedeReservar()
    {
        return $this->rol === 'jugador' && $this->estado === 'activo' && !$this->estaBaneado();
    }

    public function esAdmin()
    {
        return $this->rol === 'admin';
    }
}
