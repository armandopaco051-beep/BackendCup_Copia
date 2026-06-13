<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Formulario de preinscripcion CUP</title>
    <style>
        @page {
            margin: 28px;
        }

        body {
            margin: 0;
            color: #102033;
            font-family: DejaVu Sans, Arial, sans-serif;
            font-size: 12px;
            line-height: 1.45;
        }

        .header {
            display: table;
            width: 100%;
            padding-bottom: 16px;
            border-bottom: 3px solid #08285c;
        }

        .brand,
        .folio {
            display: table-cell;
            vertical-align: middle;
        }

        .brand {
            width: 70%;
        }

        .brand img {
            width: 64px;
            height: 64px;
            object-fit: contain;
            vertical-align: middle;
            margin-right: 12px;
        }

        .brand-text {
            display: inline-block;
            vertical-align: middle;
        }

        .brand-text strong,
        .brand-text span {
            display: block;
        }

        .brand-text strong {
            color: #061b3a;
            font-size: 16px;
            text-transform: uppercase;
        }

        .brand-text span {
            margin-top: 4px;
            color: #5d6b80;
            font-size: 11px;
            letter-spacing: 1px;
            text-transform: uppercase;
        }

        .folio {
            width: 30%;
            text-align: right;
        }

        .folio span {
            display: block;
            color: #5d6b80;
            font-size: 10px;
            font-weight: bold;
            letter-spacing: 1px;
            text-transform: uppercase;
        }

        .folio strong {
            display: block;
            margin-top: 6px;
            color: #08285c;
            font-size: 18px;
        }

        h1 {
            margin: 22px 0 6px;
            color: #061b3a;
            font-size: 22px;
            text-align: center;
            text-transform: uppercase;
        }

        .subtitle {
            margin: 0 0 18px;
            color: #5d6b80;
            text-align: center;
        }

        .status {
            margin: 0 0 18px;
            padding: 10px 12px;
            border: 1px solid #bcebd0;
            border-radius: 6px;
            color: #176b3a;
            background: #edfdf3;
            font-weight: bold;
            text-align: center;
        }

        .section {
            margin-top: 16px;
            page-break-inside: avoid;
        }

        .section-title {
            margin: 0 0 8px;
            padding: 8px 10px;
            color: #ffffff;
            background: #08285c;
            font-size: 12px;
            font-weight: bold;
            letter-spacing: 0.5px;
            text-transform: uppercase;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th,
        td {
            padding: 8px 9px;
            border: 1px solid #d9e1ee;
            vertical-align: top;
        }

        th {
            width: 28%;
            color: #34465f;
            background: #f4f7fb;
            font-size: 11px;
            text-align: left;
            text-transform: uppercase;
        }

        td {
            color: #07111f;
            font-weight: bold;
        }

        .two-columns {
            display: table;
            width: 100%;
            border-spacing: 0;
        }

        .column {
            display: table-cell;
            width: 50%;
            vertical-align: top;
        }

        .column:first-child {
            padding-right: 8px;
        }

        .column:last-child {
            padding-left: 8px;
        }

        .signatures {
            display: table;
            width: 100%;
            margin-top: 34px;
            table-layout: fixed;
        }

        .signature {
            display: table-cell;
            padding: 0 14px;
            text-align: center;
        }

        .signature-line {
            height: 34px;
            border-bottom: 1px solid #102033;
            margin-bottom: 8px;
        }

        .note {
            margin-top: 18px;
            padding: 10px 12px;
            border: 1px solid #f1ce73;
            border-radius: 6px;
            color: #6c4b00;
            background: #fff8df;
            font-size: 11px;
        }

        .footer {
            margin-top: 20px;
            color: #5d6b80;
            font-size: 10px;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="brand">
            @if ($logoPath)
                <img src="{{ $logoPath }}" alt="FICCT">
            @endif
            <div class="brand-text">
                <strong>Universidad Autonoma Gabriel Rene Moreno</strong>
                <span>Facultad FICCT - Curso Preuniversitario</span>
            </div>
        </div>
        <div class="folio">
            <span>Folio</span>
            <strong>{{ strtoupper($postulante->username_postulante) }}</strong>
            <span>Emitido: {{ $fechaEmision->format('d/m/Y H:i') }}</span>
        </div>
    </div>

    <h1>Formulario de Preinscripcion</h1>
    <p class="subtitle">Comprobante para impresion y presentacion en ventanilla.</p>
    <div class="status">Estado de matricula: {{ strtoupper($pago->estado) }} - Pago confirmado</div>

    <div class="section">
        <div class="section-title">Datos personales</div>
        <div class="two-columns">
            <div class="column">
                <table>
                    <tr><th>Nombre completo</th><td>{{ $postulante->nombre }}</td></tr>
                    <tr><th>CI</th><td>{{ $postulante->ci }}</td></tr>
                    <tr><th>Correo</th><td>{{ $postulante->correo }}</td></tr>
                    <tr><th>Telefono</th><td>{{ $postulante->telefono }}</td></tr>
                    <tr><th>Ciudad</th><td>{{ $postulante->ciudad }}</td></tr>
                </table>
            </div>
            <div class="column">
                <table>
                    <tr><th>Direccion</th><td>{{ $postulante->direccion }}</td></tr>
                    <tr><th>Fecha nacimiento</th><td>{{ \Illuminate\Support\Carbon::parse($postulante->fecha_nacimiento)->format('d/m/Y') }}</td></tr>
                    <tr><th>Genero</th><td>{{ $postulante->genero }}</td></tr>
                    <tr><th>Colegio</th><td>{{ $postulante->colegio_procedencia }}</td></tr>
                    <tr><th>Cod. titulo</th><td>{{ $postulante->cod_titulo_bachiller }}</td></tr>
                </table>
            </div>
        </div>
    </div>

    <div class="section">
        <div class="section-title">Carreras seleccionadas</div>
        <table>
            <tr>
                <th>Opcion</th>
                <th>Carrera</th>
                <th>Codigo</th>
            </tr>
            @forelse ($carreras as $index => $carrera)
                <tr>
                    <td>{{ $carrera['descripcion'] ?? ($index === 0 ? 'Primera opcion' : 'Segunda opcion') }}</td>
                    <td>{{ $carrera['nombre'] }}</td>
                    <td>{{ $carrera['codigo'] }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="3">Sin carreras registradas.</td>
                </tr>
            @endforelse
        </table>
    </div>

    <div class="section">
        <div class="section-title">Pago de matricula</div>
        <table>
            <tr><th>Monto</th><td>{{ $pago->monto }} Bs</td></tr>
            <tr><th>Estado</th><td>{{ strtoupper($pago->estado) }}</td></tr>
            <tr><th>Nro. comprobante</th><td>{{ $pago->nro_comprobante }}</td></tr>
            <tr><th>Fecha pago</th><td>{{ \Illuminate\Support\Carbon::parse($pago->fecha_pago)->format('d/m/Y') }}</td></tr>
            <tr><th>Observacion</th><td>{{ $pago->observacion ?? 'Pago confirmado.' }}</td></tr>
        </table>
    </div>

    <div class="section">
        <div class="section-title">Control de ventanilla</div>
        <table>
            <tr><th>Fotocopia de cedula</th><td>Entregado: ________ Observacion: ______________________________</td></tr>
            <tr><th>Diploma de bachiller</th><td>Entregado: ________ Observacion: ______________________________</td></tr>
            <tr><th>Libretas escolares</th><td>Entregado: ________ Observacion: ______________________________</td></tr>
        </table>
    </div>

    <div class="signatures">
        <div class="signature">
            <div class="signature-line"></div>
            Firma del postulante
        </div>
        <div class="signature">
            <div class="signature-line"></div>
            Revision ventanilla
        </div>
    </div>

    <div class="note">
        Este formulario acredita la preinscripcion y el pago de matricula. La habilitacion final queda sujeta a la validacion fisica de documentos y al proceso academico correspondiente.
    </div>

    <div class="footer">
        Documento generado automaticamente por el Portal de Admision CUP - FICCT.
    </div>
</body>
</html>
