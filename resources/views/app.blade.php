<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#2196F3">
    <meta name="description" content="Family Hub - Gestion familiar compartida: lista de compra, calendario, tareas y cumpleanos">
    <meta property="og:title" content="{{ config('app.name') }}">
    <meta property="og:description" content="Gestion familiar compartida">
    <meta property="og:type" content="website">
    <meta property="og:url" content="{{ config('app.url') }}">
    <title>{{ config('app.name') }}</title>
    <link rel="icon" href="/favicon.ico">
    <link rel="apple-touch-icon" href="/icons/icon-192x192.png">
    @vite(['resources/css/app.css', 'resources/js/app.ts'])
</head>
<body>
    <div id="app"></div>
</body>
</html>
