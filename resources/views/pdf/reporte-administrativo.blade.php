<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>{{ $reporte['titulo'] }}</title>
    <style>
        @page { margin: 28px; }
        body { color: #172033; font-family: DejaVu Sans, sans-serif; font-size: 9px; }
        .header { display: table; width: 100%; padding-bottom: 14px; border-bottom: 2px solid #08285c; }
        .brand, .meta { display: table-cell; vertical-align: middle; }
        .brand img { width: 58px; vertical-align: middle; }
        .brand div { display: inline-block; margin-left: 12px; vertical-align: middle; }
        .brand strong { display: block; color: #08285c; font-size: 15px; }
        .brand span { display: block; margin-top: 3px; color: #5d6b80; }
        .meta { text-align: right; }
        h1 { margin: 18px 0 6px; color: #061b3a; font-size: 21px; }
        .filters { margin: 0 0 14px; color: #5d6b80; }
        .summary { margin-bottom: 14px; }
        .summary span { display: inline-block; margin: 0 6px 5px 0; padding: 6px 9px; background: #eef4ff; color: #08285c; }
        table { width: 100%; border-collapse: collapse; table-layout: fixed; }
        th, td { padding: 6px 5px; border: 1px solid #d9e1ee; overflow-wrap: anywhere; }
        th { color: #fff; background: #08285c; font-size: 8px; text-transform: uppercase; }
        tr:nth-child(even) td { background: #f7f9fc; }
        .footer { margin-top: 12px; color: #6b7280; text-align: right; }
    </style>
</head>
<body>
    <header class="header">
        <div class="brand">
            @if ($logoPath)
                <img src="{{ $logoPath }}" alt="FICCT">
            @endif
            <div>
                <strong>Universidad Autonoma Gabriel Rene Moreno</strong>
                <span>Facultad de Ingenieria en Ciencias de la Computacion y Telecomunicaciones</span>
            </div>
        </div>
        <div class="meta">
            <strong>CUP - UAGRM</strong><br>
            Generado: {{ $reporte['generado_en'] }}<br>
            Usuario: {{ $usuario?->username ?? 'Sistema' }}
        </div>
    </header>

    <h1>{{ $reporte['titulo'] }}</h1>
    <p class="filters">
        Filtros:
        @forelse (collect($reporte['filtros'])->except('limite')->filter() as $campo => $valor)
            {{ str_replace('_', ' ', ucfirst($campo)) }}: {{ $valor }}{{ $loop->last ? '' : ' | ' }}
        @empty
            Sin filtros adicionales
        @endforelse
    </p>

    <div class="summary">
        @foreach ($reporte['resumen'] as $campo => $valor)
            <span><strong>{{ str_replace('_', ' ', ucfirst($campo)) }}:</strong> {{ $valor }}</span>
        @endforeach
    </div>

    <table>
        <thead>
            <tr>
                @foreach ($reporte['columnas'] as $titulo)
                    <th>{{ $titulo }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @forelse ($reporte['datos'] as $fila)
                <tr>
                    @foreach (array_keys($reporte['columnas']) as $campo)
                        <td>{{ $fila[$campo] ?? '-' }}</td>
                    @endforeach
                </tr>
            @empty
                <tr>
                    <td colspan="{{ count($reporte['columnas']) }}">No existen datos para los filtros seleccionados.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <p class="footer">CU-32 Generar reportes PDF · Datos reales del sistema CUP</p>
</body>
</html>
