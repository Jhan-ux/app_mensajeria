<!DOCTYPE html>
<html lang="es" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'Mensajería Anónima') }} - @yield('title', 'Chat Seguro')</title>
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,400;0,600;0,700;1,400;1,600;1,700&family=Inter:wght@300;400;500;600;700&family=Outfit:wght@400;500;600;700;800&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">

    <!-- Estilos de la aplicación -->
    <link rel="stylesheet" href="{{ asset('css/theme.css') }}?v={{ time() }}">
    
    @yield('styles')
</head>
<body class="bg-dark text-light antialiased font-sans">
    @yield('content')

    <!-- Scripts compartidos -->
    @yield('scripts')
</body>
</html>
