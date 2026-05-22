@extends('layouts.app')

@section('content')
<h1 class="mb-5 text-2xl font-bold">Statusuebersicht</h1>
<div class="grid gap-4 md:grid-cols-3 lg:grid-cols-6">
    @foreach(\App\Models\Vehicle::STATUSES as $status => $label)
        <a href="{{ route('vehicles.index', ['status' => $status]) }}" class="rounded bg-white p-4 shadow hover:shadow-md">
            <div class="text-sm text-slate-500">{{ $label }}</div>
            <div class="mt-2 text-3xl font-bold">{{ $counts[$status] ?? 0 }}</div>
        </a>
    @endforeach
</div>

<div class="mt-6 grid gap-6 lg:grid-cols-2">
    <section class="rounded bg-white p-4 shadow">
        <h2 class="mb-3 text-lg font-semibold">Schnellaktionen</h2>
        <div class="flex flex-wrap gap-3">
            <a href="{{ route('vehicles.index') }}" class="rounded bg-blue-600 px-4 py-2 text-white">Fahrzeugpool</a>
            <a href="{{ route('loans.create') }}" class="rounded bg-emerald-600 px-4 py-2 text-white">Fahrzeug verleihen</a>
            @if(auth()->user()->isAdmin())
                <a href="{{ route('vehicles.create') }}" class="rounded bg-slate-700 px-4 py-2 text-white">Fahrzeug anlegen</a>
                <a href="{{ route('vehicles.import.form') }}" class="rounded bg-slate-700 px-4 py-2 text-white">Excel importieren</a>
            @endif
        </div>
    </section>

    <section class="rounded bg-white p-4 shadow">
        <h2 class="mb-3 text-lg font-semibold">Ueberfaellige Rueckgaben</h2>
        @forelse($overdueLoans as $loan)
            <div class="border-b py-2 text-sm">
                <strong>{{ $loan->vehicle->inventory_number }}</strong> an {{ $loan->borrower_name }} bis {{ $loan->planned_return_at->format('d.m.Y H:i') }}
            </div>
        @empty
            <p class="text-slate-500">Keine ueberfaelligen Rueckgaben.</p>
        @endforelse
    </section>
</div>
@endsection
