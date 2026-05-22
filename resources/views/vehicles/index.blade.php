@extends('layouts.app')

@section('content')
<div class="mb-4 flex flex-wrap items-center justify-between gap-3">
    <h1 class="text-2xl font-bold">Fahrzeugpool</h1>
    <a href="{{ route('vehicles.create') }}" class="rounded bg-slate-900 px-4 py-2 text-white">Fahrzeug anlegen</a>
</div>

<form class="mb-4 grid gap-3 rounded bg-white p-4 shadow md:grid-cols-4">
    <input name="search" value="{{ request('search') }}" placeholder="Suche" class="rounded border p-2">
    <select name="status" class="rounded border p-2">
        <option value="">Alle Status</option>
        @foreach($statuses as $key => $label)
            <option value="{{ $key }}" @selected(request('status') === $key)>{{ $label }}</option>
        @endforeach
    </select>
    <select name="category" class="rounded border p-2">
        <option value="">Alle Kategorien</option>
        @foreach($categories as $category)
            <option value="{{ $category->id }}" @selected((int) request('category') === $category->id)>{{ $category->name }}</option>
        @endforeach
    </select>
    <button class="rounded bg-blue-600 px-4 py-2 text-white">Filtern</button>
</form>

<div class="overflow-hidden rounded bg-white shadow">
    <table class="w-full text-left text-sm">
        <thead class="bg-slate-100"><tr><th class="p-3">Fahrzeug</th><th>Kategorie</th><th>Status</th><th>Standort</th><th>Aktionen</th></tr></thead>
        <tbody>
            @foreach($vehicles as $vehicle)
                <tr class="border-t">
                    <td class="p-3"><a class="font-semibold text-blue-700" href="{{ route('vehicles.show', $vehicle) }}">{{ $vehicle->inventory_number }}</a><br><span class="text-slate-500">{{ $vehicle->manufacturer }} {{ $vehicle->model }}</span></td>
                    <td>{{ $vehicle->category?->name }}</td>
                    <td><span class="rounded bg-slate-100 px-2 py-1">{{ $vehicle->statusLabel() }}</span></td>
                    <td>{{ $vehicle->location }}</td>
                    <td class="space-x-2">
                        @if($vehicle->isAvailable())<a class="text-emerald-700" href="{{ route('vehicles.loans.create', $vehicle) }}">Verleihen</a>@endif
                        <a class="text-blue-700" href="{{ route('vehicles.check-in.form', $vehicle) }}">Check-in</a>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
<div class="mt-4">{{ $vehicles->links() }}</div>
@endsection
