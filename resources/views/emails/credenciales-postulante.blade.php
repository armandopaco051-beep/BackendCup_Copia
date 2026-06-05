<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Credenciales CUP</title>
</head>
<body style="font-family: Arial, sans-serif; color: #102033; line-height: 1.5; margin: 0; padding: 24px;">
    <h2 style="margin-top: 0;">Credenciales de acceso CUP</h2>
    <p>Hola {{ $nombre }}, tu postulacion fue habilitada correctamente.</p>
    <p>Desde este momento puedes ingresar al portal con las siguientes credenciales:</p>
    <div style="padding: 16px; border: 1px solid #d9e1ee; border-radius: 8px; background: #f8fafc;">
        <p style="margin: 0 0 8px;"><strong>Usuario:</strong> {{ $username }}</p>
        <p style="margin: 0;"><strong>Password temporal:</strong> {{ $passwordTemporal }}</p>
    </div>
    <p>Por seguridad, cambia tu password despues de iniciar sesion.</p>
    <p style="margin-bottom: 0;">Universidad Autonoma Gabriel Rene Moreno - FICCT</p>
</body>
</html>
