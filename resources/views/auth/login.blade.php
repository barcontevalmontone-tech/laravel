<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login · {{ config('app.name', 'Gestionale') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-slate-950 text-slate-100 flex items-center justify-center">
    {{-- Stub provvisorio: la logica di autenticazione completa (Breeze/Jetstream) sarà aggiunta in seguito. --}}
    <main class="w-full max-w-sm rounded-2xl bg-slate-900 p-8 shadow-2xl border border-slate-800">
        <h1 class="text-2xl font-bold mb-2">Accesso operatore</h1>
        <p class="text-sm text-slate-400 mb-6">Autenticazione non ancora configurata: usa i token Sanctum per l'API o il seeder per creare utenti.</p>
        <a href="{{ url('/magazzino') }}" class="inline-block rounded-lg bg-indigo-600 px-6 py-3 font-semibold hover:bg-indigo-500">Vai al magazzino</a>
    </main>
</body>
</html>
