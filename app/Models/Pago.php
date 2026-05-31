<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Pago extends Model
{
    protected $table = 'pago.pago';

    public $timestamps = false;

    protected $fillable = [
        'username_postulante',
        'monto',
        'nro_comprobante',
        'fecha_pago',
        'registrado_por',
        'estado',
        'observacion',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'monto' => 'decimal:2',
            'fecha_pago' => 'date',
            'created_at' => 'datetime',
        ];
    }

    public function postulante(): BelongsTo
    {
        return $this->belongsTo(Postulante::class, 'username_postulante', 'username_postulante');
    }

    public function registradoPor(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'registrado_por', 'username');
    }
}
