<?php

namespace App\Models;

use App\Models\Concerns\HasPostgresSchema;
use Illuminate\Database\Eloquent\Model;

class PonderacionNota extends Model
{
    use HasPostgresSchema;

    protected string $schema = 'academico';

    protected $table = 'ponderacion_nota';

    protected $primaryKey = 'id';

    public $timestamps = false;

    protected $fillable = [
        'nombre',
        'nota1_porcentaje',
        'nota2_porcentaje',
        'nota3_porcentaje',
        'estado',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'nota1_porcentaje' => 'float',
            'nota2_porcentaje' => 'float',
            'nota3_porcentaje' => 'float',
            'created_at' => 'datetime',
        ];
    }

    public static function activa(): self
    {
        return self::where('estado', 'activa')->orderByDesc('id')->first()
            ?? self::create([
                'nombre' => 'Ponderacion CUP',
                'nota1_porcentaje' => 30,
                'nota2_porcentaje' => 30,
                'nota3_porcentaje' => 40,
                'estado' => 'activa',
                'created_at' => now(),
            ]);
    }
}
