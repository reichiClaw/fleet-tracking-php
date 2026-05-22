@extends('layouts.app')

@section('content')
<div class="mx-auto max-w-md rounded bg-white p-6 shadow">
    <h1 class="mb-4 text-2xl font-bold">Login</h1>
    <form method="post" action="{{ route('login.store') }}" class="space-y-4">
        @csrf
        <label class="block">E-Mail
            <input name="email" type="email" value="{{ old('email') }}" required class="mt-1 w-full rounded border p-2">
        </label>
        <label class="block">Passwort
            <input name="password" type="password" required class="mt-1 w-full rounded border p-2">
        </label>
        <label class="flex items-center gap-2"><input type="checkbox" name="remember"> Angemeldet bleiben</label>
        <button class="w-full rounded bg-slate-900 px-4 py-2 font-semibold text-white">Einloggen</button>
    </form>
</div>
@endsection
