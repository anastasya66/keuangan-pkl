<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>FinToko — Financial Management System</title>
    @viteReactRefresh
    @vite(['resources/css/app.css', 'resources/js/main.jsx'])
</head>
<body class="bg-slate-900 text-slate-100 antialiased min-h-screen">
    <div id="root"></div>
</body>
</html>
