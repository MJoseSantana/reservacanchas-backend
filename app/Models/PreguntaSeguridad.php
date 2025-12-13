<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Hash;

class PreguntaSeguridad extends Model
{
    use HasFactory;

    protected $table = 'preguntas_seguridad';

    protected $fillable = [
        'usuario_id',
        'pregunta',
        'respuesta',
        'orden',
    ];

    protected $hidden = [
        'respuesta',
    ];

    public function usuario()
    {
        return $this->belongsTo(Usuario::class, 'usuario_id');
    }

    public function setRespuestaAttribute($value)
    {
        $this->attributes['respuesta'] = Hash::make(strtolower(trim($value)));
    }

    public function verificarRespuesta(string $respuesta): bool
    {
        return Hash::check(strtolower(trim($respuesta)), $this->respuesta);
    }
}
