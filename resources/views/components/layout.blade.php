<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ $title ?? 'Magazzino' }} · {{ config('app.name', 'Gestionale') }}</title>

        @fonts

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="min-h-screen bg-slate-100 text-slate-900 antialiased">
        <nav class="bg-slate-900 text-white">
            <div class="mx-auto flex max-w-6xl items-center justify-between px-4 py-3">
                <a href="{{ route('magazzino.dashboard') }}" class="text-lg font-semibold tracking-wide">
                    📦 Gestionale Magazzino
                </a>
                <div class="flex items-center gap-4 text-sm">
                    <a href="{{ route('magazzino.dashboard') }}"
                       class="rounded-md px-3 py-1.5 hover:bg-slate-700 {{ request()->routeIs('magazzino.dashboard') ? 'bg-slate-700' : '' }}">
                        Dashboard
                    </a>
                    <a href="{{ route('magazzino.articoli.index') }}"
                       class="rounded-md px-3 py-1.5 hover:bg-slate-700 {{ request()->routeIs('magazzino.articoli.*') ? 'bg-slate-700' : '' }}">
                        Articoli
                    </a>
                </div>
            </div>
        </nav>

        <main class="mx-auto max-w-6xl px-4 py-8">
            @if (session('status'))
                <div class="mb-4 rounded-md border border-emerald-300 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
                    {{ session('status') }}
                </div>
            @endif

            @if (session('error'))
                <div class="mb-4 rounded-md border border-red-300 bg-red-50 px-4 py-3 text-sm text-red-800">
                    {{ session('error') }}
                </div>
            @endif

            @if ($errors->any())
                <div class="mb-4 rounded-md border border-red-300 bg-red-50 px-4 py-3 text-sm text-red-800">
                    <ul class="list-inside list-disc space-y-1">
                        @foreach ($errors->all() as $errore)
                            <li>{{ $errore }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            {{ $slot }}
        </main>
    </body>
</html>
