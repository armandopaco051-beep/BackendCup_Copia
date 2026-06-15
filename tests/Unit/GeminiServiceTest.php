<?php

namespace Tests\Unit;

use App\Services\GeminiService;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class GeminiServiceTest extends TestCase
{
    public function test_fails_with_a_controlled_message_when_api_key_is_missing(): void
    {
        config(['services.gemini.key' => '']);

        $this->expectException(ValidationException::class);

        app(GeminiService::class)->interpretarReporte(
            'Muestra los postulantes admitidos.',
            $this->catalogos(),
        );
    }

    public function test_returns_the_structured_report_interpretation_from_gemini(): void
    {
        config([
            'services.gemini.key' => 'test-key',
            'services.gemini.model' => 'gemini-2.5-flash',
        ]);

        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [[
                    'content' => [
                        'parts' => [[
                            'text' => json_encode([
                                'accion' => 'exportar',
                                'tipo' => 'postulantes',
                                'formato' => 'pdf',
                                'respuesta' => 'Generare el reporte solicitado.',
                                'filtros' => [
                                    'periodo' => 1,
                                    'carrera' => 'SIS',
                                    'estado' => 'admitido',
                                ],
                            ]),
                        ]],
                    ],
                ]],
            ]),
        ]);

        $result = app(GeminiService::class)->interpretarReporte(
            'Genera un PDF de admitidos de Sistemas.',
            $this->catalogos(),
        );

        $this->assertSame('postulantes', $result['tipo']);
        $this->assertSame('pdf', $result['formato']);
        $this->assertSame('SIS', $result['filtros']['carrera']);

        Http::assertSent(fn ($request): bool => str_contains($request->url(), 'gemini-2.5-flash:generateContent')
            && $request->hasHeader('x-goog-api-key', 'test-key'));
    }

    private function catalogos(): array
    {
        return [
            'fecha_actual' => '2026-06-14',
            'periodos' => [
                ['id' => 1, 'nombre' => 'Periodo CUP 2026-1'],
            ],
            'carreras' => [
                ['codigo' => 'SIS', 'nombre' => 'Ingenieria de Sistemas'],
            ],
            'grupos' => [
                ['codigo' => 'Grupo-G01'],
            ],
        ];
    }
}
