<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;

class GeminiService
{
    public function interpretarReporte(string $comando, array $catalogos): array
    {
        $apiKey = (string) config('services.gemini.key');
        $model = (string) config('services.gemini.model', 'gemini-2.5-flash');

        if ($apiKey === '') {
            throw ValidationException::withMessages([
                'gemini' => ['Falta configurar GEMINI_API_KEY en el archivo .env.'],
            ]);
        }

        $response = Http::acceptJson()
            ->withHeaders(['x-goog-api-key' => $apiKey])
            ->timeout(30)
            ->retry(2, 350, throw: false)
            ->post("https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent", [
                'contents' => [[
                    'role' => 'user',
                    'parts' => [[
                        'text' => $this->prompt($comando, $catalogos),
                    ]],
                ]],
                'generationConfig' => [
                    'temperature' => 0.1,
                    'responseMimeType' => 'application/json',
                    'responseSchema' => $this->schema(),
                ],
            ]);

        if ($response->failed()) {
            $message = $response->json('error.message')
                ?: 'Gemini no pudo interpretar el comando.';

            throw ValidationException::withMessages([
                'gemini' => [$message],
            ]);
        }

        $text = $response->json('candidates.0.content.parts.0.text');
        $result = is_string($text) ? json_decode($text, true) : null;

        if (! is_array($result)) {
            throw ValidationException::withMessages([
                'gemini' => ['Gemini devolvio una respuesta que no pudo procesarse.'],
            ]);
        }

        return $result;
    }
    // hace el  prompt para gemini
    private function prompt(string $comando, array $catalogos): string
    {
        $periodos = collect($catalogos['periodos'])
            ->map(fn (array $periodo): string => "{$periodo['id']} = {$periodo['nombre']}")
            ->join(', ');
        $carreras = collect($catalogos['carreras'])
            ->map(fn (array $carrera): string => "{$carrera['codigo']} = {$carrera['nombre']}")
            ->join(', ');
        $grupos = collect($catalogos['grupos'])
            ->map(fn (array $grupo): string => $grupo['codigo'])
            ->join(', ');
        $docentes = collect($catalogos['docentes'] ?? [])
            ->map(fn (array $docente): string => "{$docente['username']} = {$docente['nombre']}")
            ->join(', ');
        $materias = collect($catalogos['materias'] ?? [])
            ->map(fn (array $materia): string => "{$materia['id']} = {$materia['nombre']}")
            ->join(', ');
        $tipos = collect($catalogos['tipos'] ?? [])
            ->map(fn (array $tipo): string => "- {$tipo['codigo']}: {$tipo['nombre']}.")
            ->join("\n");

        return <<<PROMPT
Eres el interprete de comandos de reportes del sistema CUP UAGRM.
Convierte la solicitud en los filtros permitidos. No inventes identificadores.

Fecha actual: {$catalogos['fecha_actual']}.
Tipos permitidos:
{$tipos}
Formatos: pantalla, pdf, excel.
Periodos disponibles: {$periodos}.
Carreras disponibles: {$carreras}.
Grupos disponibles: {$grupos}.
Docentes disponibles: {$docentes}.
Materias disponibles: {$materias}.

Estados por tipo:
- postulantes: pendiente, pagado, validado, habilitado, admitido, rechazado.
- lista_admitidos: sin estado.
- postulantes_aprobados y postulantes_reprobados: sin estado adicional.
- pagos: pendiente, registrado, pagado, rechazado.
- calificaciones: aprobado, reprobado.
- resultados_estudiantes: aprobado, reprobado.
- docentes_grupo: propuesto, confirmado.
- estadisticas_materia, grupos_habilitados y rendimiento_grupos: sin estado.

Reglas:
- Usa el ID exacto del periodo, el codigo exacto de carrera y el codigo exacto del grupo.
- Si el usuario menciona un docente o profesor, usa el username exacto en el filtro docente.
- Si el usuario menciona una materia, usa el ID exacto en el filtro materia.
- Si el usuario pide "mostrar", "consultar" o no indica descarga, formato = pantalla.
- Si pide PDF o Excel, accion = exportar y usa ese formato.
- "Este mes" significa desde el primer dia hasta el ultimo dia del mes actual.
- "Hoy" usa la fecha actual en fecha_inicio y fecha_fin.
- Omite los filtros que no fueron solicitados.
- Si solicita "postulantes aprobados", usa postulantes_aprobados. No uses postulantes ni lista_admitidos.
- Si solicita "postulantes reprobados", usa postulantes_reprobados.
- "Aprobado" significa promedio final mayor o igual a 60; "admitido" significa que obtuvo cupo en una carrera.
- respuesta debe explicar brevemente lo interpretado en espanol.

Comando del administrador:
{$comando}
PROMPT;
    }

    private function schema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'accion' => [
                    'type' => 'string',
                    'enum' => ['consultar', 'exportar'],
                ],
                'tipo' => [
                    'type' => 'string',
                    'enum' => ReporteService::TIPOS,
                ],
                'formato' => [
                    'type' => 'string',
                    'enum' => ['pantalla', 'pdf', 'excel'],
                ],
                'respuesta' => [
                    'type' => 'string',
                ],
                'filtros' => [
                    'type' => 'object',
                    'properties' => [
                        'buscar' => ['type' => 'string'],
                        'periodo' => ['type' => 'integer'],
                        'carrera' => ['type' => 'string'],
                        'estado' => ['type' => 'string'],
                        'fecha_inicio' => ['type' => 'string'],
                        'fecha_fin' => ['type' => 'string'],
                        'grupo' => ['type' => 'string'],
                        'docente' => ['type' => 'string'],
                        'materia' => ['type' => 'string'],
                    ],
                ],
            ],
            'required' => ['accion', 'tipo', 'formato', 'respuesta', 'filtros'],
        ];
    }
}
