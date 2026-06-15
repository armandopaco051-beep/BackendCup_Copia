<?php

namespace App\Http\Controllers;

use App\Models\Docente;
use App\Models\DocenteMateria;
use App\Models\Materia;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class DocenteMateriaController extends Controller
{
    public function index(): JsonResponse
    {
        $materias = $this->materiasDisponibles();

        return response()->json([
            'docentes' => Docente::orderBy('nombre')
                ->get()
                ->map(fn (Docente $docente): array => $this->formatDocente($docente))
                ->values(),
            'materias' => $materias
                ->map(fn (Materia $materia): array => [
                    'id' => $materia->id,
                    'nombre' => $materia->nombre,
                    'estado' => $materia->estado ?? 'habilitada',
                ])
                ->values(),
        ]);
    }

    public function show(string $username): JsonResponse
    {
        $docente = Docente::where('username_docente', $username)->firstOrFail();

        return response()->json([
            'docente' => $this->formatDocente($docente),
        ]);
    }

    public function sync(Request $request, string $username): JsonResponse
    {
        $docente = Docente::where('username_docente', $username)->firstOrFail();
        $this->validarDocenteHabilitado($docente);

        $validated = $request->validate([
            'materias' => ['required', 'array'],
            'materias.*' => ['required', 'string', 'max:100', Rule::exists('pgsql.academico.materia', 'id')],
        ]);

        DB::transaction(function () use ($docente, $validated): void {
            DocenteMateria::where('username_docente', $docente->username_docente)->delete();

            foreach (array_unique($validated['materias']) as $materia) {
                DocenteMateria::create([
                    'username_docente' => $docente->username_docente,
                    'id_materia' => $materia,
                    'created_at' => now(),
                ]);
            }
        });

        return response()->json([
            'message' => 'Materias asignadas al docente correctamente.',
            'docente' => $this->formatDocente($docente),
        ]);
    }

    public function destroy(string $username, string $materia): JsonResponse
    {
        Docente::where('username_docente', $username)->firstOrFail();
        Materia::where('id', $materia)->firstOrFail();

        DocenteMateria::where('username_docente', $username)
            ->where('id_materia', $materia)
            ->delete();

        return response()->json([
            'message' => 'Materia quitada del docente correctamente.',
        ]);
    }

    private function formatDocente(Docente $docente): array
    {
        $materiasAsignadas = DB::table('academico.docente_materia')
            ->where('username_docente', $docente->username_docente)
            ->pluck('id_materia')
            ->values()
            ->all();

        $materias = Materia::whereIn('id', $materiasAsignadas)
            ->orderBy('nombre')
            ->get(['id', 'nombre', 'estado'])
            ->map(fn (Materia $materia): array => [
                'id' => $materia->id,
                'nombre' => $materia->nombre,
                'estado' => $materia->estado ?? 'habilitada',
            ])
            ->values();

        return [
            'username' => $docente->username_docente,
            'nombre' => $docente->nombre,
            'correo' => $docente->correo,
            'titulo_profesional' => $docente->titulo_profesional,
            'nro_registro_profesional' => $docente->nro_registro_profesional,
            'estado_profesional' => $docente->estado_profesional ?? 'pendiente_revision',
            'observacion_profesional' => $docente->observacion_profesional,
            'max_grupos_periodo' => $docente->max_grupos_periodo ?? 3,
            'max_horas_semana' => $docente->max_horas_semana ?? 30,
            'especializacion' => $docente->especializacion,
            'maestria' => $docente->maestria,
            'materias_ids' => $materiasAsignadas,
            'materias' => $materias,
        ];
    }

    private function validarDocenteHabilitado(Docente $docente): void
    {
        if (! $docente->estaHabilitadoProfesionalmente()) {
            throw ValidationException::withMessages([
                'username_docente' => ['El docente no esta habilitado profesionalmente para impartir materias.'],
            ]);
        }
    }

    private function materiasDisponibles()
    {
        $query = Materia::orderBy('nombre');

        if (DB::table('information_schema.columns')
            ->where('table_schema', 'academico')
            ->where('table_name', 'materia')
            ->where('column_name', 'estado')
            ->exists()) {
            $query->where('estado', 'habilitada');
        }

        return $query->get(['id', 'nombre', 'estado']);
    }
}
