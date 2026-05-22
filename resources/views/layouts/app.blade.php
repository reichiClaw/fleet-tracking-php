<!doctype html>
<html lang="de">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name', 'Fuhrpark Management') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-slate-100 text-slate-900">
    <header class="bg-slate-900 text-white shadow">
        <div class="mx-auto flex max-w-7xl flex-wrap items-center justify-between gap-3 px-4 py-4">
            <a href="{{ route('dashboard') }}" class="text-lg font-bold">Fuhrpark Management</a>
            @auth
                <nav class="flex flex-wrap gap-3 text-sm">
                    <a href="{{ route('dashboard') }}" class="hover:underline">Dashboard</a>
                    <a href="{{ route('vehicles.index') }}" class="hover:underline">Fahrzeugpool</a>
                    <a href="{{ route('loans.index') }}" class="hover:underline">Verleih</a>
                    <a href="{{ route('drivers.index') }}" class="hover:underline">Fahrer</a>
                    @if(auth()->user()->isAdmin())
                        <a href="{{ route('vehicles.import.form') }}" class="hover:underline">Import</a>
                        <a href="{{ route('categories.index') }}" class="hover:underline">Kategorien</a>
                        <a href="{{ route('users.index') }}" class="hover:underline">Benutzer</a>
                    @endif
                </nav>
                <form method="post" action="{{ route('logout') }}">
                    @csrf
                    <button class="rounded bg-slate-700 px-3 py-1 text-sm hover:bg-slate-600">Logout</button>
                </form>
            @endauth
        </div>
    </header>

    <main class="mx-auto max-w-7xl px-4 py-6">
        @if(session('status'))
            <div class="mb-4 rounded border border-emerald-200 bg-emerald-50 p-3 text-emerald-800">{{ session('status') }}</div>
        @endif
        @if($errors->any())
            <div class="mb-4 rounded border border-red-200 bg-red-50 p-3 text-red-800">
                <ul class="list-disc pl-5">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif
        @yield('content')
    </main>
</body>
</html>
