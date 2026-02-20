<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">

        <title>{{ $title ?? config('app.name') }}</title>

        <script src="https://cdn.tailwindcss.com"></script>
    </head>
    <body class="min-h-screen text-[#dbdee1] bg-[#313338]">
        {{ $slot }}
    </body>
</html>
