<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ config('app.name', 'Laravel') }}</title>
    <link rel="icon" type="image/x-icon" href="{{ asset('assets/img/favicon.ico') }}">
</head>
<body>
    <div style="display: flex; justify-content: center; align-items: center; height: 100vh; flex-direction: column;">
        {{-- mostrar el logo centrado con el año actual debajo --}}
        <div style="text-align: center;">
            <img src="{{ asset('assets/img/qr.png') }}" alt="{{ config('app.name', 'Laravel') }} logo" style="max-width: 100%; height: auto; width: 300px;">
            <h1 style="color: #007bff;">{{ config('app.name', 'Notificaciones') }}</h1>
            <p>&copy; {{ date('Y') }} - Todos los derechos reservados</p>
        </div>
    </div>
</body>
</html>
