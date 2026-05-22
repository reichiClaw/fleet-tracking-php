@extends('layouts.app')

@section('content')
<div class="mb-4 flex flex-wrap items-center justify-between gap-3">
    <div><h1 class="text-2xl font-bold">{{ $vehicle->inventory_number }}</h1><p>{{ $vehicle->manufacturer }} {{ $vehicle->model }} · {{ $vehicle->category?->name }}</p></div>
    <div class="flex flex-wrap gap-2">
        @if($vehicle->isAvailable())<a href="{{ route('vehicles.loans.create', $vehicle) }}" class="rounded bg-emerald-600 px-4 py-2 text-white">Verleihen</a>@endif
        <a href="{{ route('vehicles.check-in.form', $vehicle) }}" class="rounded bg-blue-600 px-4 py-2 text-white">Check-in</a>
        <a href="{{ route('vehicles.manufacturer-checkout.form', $vehicle) }}" class="rounded bg-amber-600 px-4 py-2 text-white">Hersteller-Auschecken</a>
        <a href="{{ route('vehicles.edit', $vehicle) }}" class="rounded bg-slate-700 px-4 py-2 text-white">Bearbeiten</a>
    </div>
</div>

<div class="grid gap-6 lg:grid-cols-3">
    <section class="rounded bg-white p-4 shadow lg:col-span-2">
        <h2 class="mb-3 text-lg font-semibold">Status</h2>
        <dl class="grid gap-3 md:grid-cols-2">
            <div><dt class="text-sm text-slate-500">Status</dt><dd class="font-semibold">{{ $vehicle->statusLabel() }}</dd></div>
            <div><dt class="text-sm text-slate-500">Standort</dt><dd>{{ $vehicle->location ?: '-' }}</dd></div>
            <div><dt class="text-sm text-slate-500">KM</dt><dd>{{ $vehicle->current_km }}</dd></div>
            <div><dt class="text-sm text-slate-500">Betriebsstunden</dt><dd>{{ $vehicle->current_operating_hours }}</dd></div>
        </dl>
        @if($vehicle->activeLoan)
            <div class="mt-4 rounded bg-amber-50 p-3 text-amber-900">Aktiv verliehen an {{ $vehicle->activeLoan->borrower_name }} bis {{ $vehicle->activeLoan->planned_return_at->format('d.m.Y H:i') }}. <a class="underline" href="{{ route('loans.return.form', $vehicle->activeLoan) }}">Rueckgabe erfassen</a></div>
        @endif
    </section>
    <section class="rounded bg-white p-4 text-center shadow">
        <h2 class="mb-3 text-lg font-semibold">QR-Code</h2>
        <div class="inline-block max-w-56">{!! $qrSvg !!}</div>
        <a class="mt-3 block text-blue-700" href="{{ route('vehicles.qr-label', $vehicle) }}">Druckansicht</a>
    </section>
</div>

<div class="mt-6 grid gap-6 lg:grid-cols-2">
    <section class="rounded bg-white p-4 shadow">
        <h2 class="mb-3 text-lg font-semibold">Historie</h2>
        @forelse($vehicle->inspections as $inspection)
            <div class="border-b py-2 text-sm"><strong>{{ $inspection->type }}</strong> am {{ $inspection->occurred_at->format('d.m.Y H:i') }} · KM {{ $inspection->km }} · Std {{ $inspection->operating_hours }}<br>{{ $inspection->condition_notes }}</div>
        @empty <p class="text-slate-500">Noch keine Protokolle.</p> @endforelse
    </section>
    <section class="rounded bg-white p-4 shadow">
        <h2 class="mb-3 text-lg font-semibold">Schäden</h2>
        @forelse($vehicle->damages as $damage)
            <div class="border-b py-2 text-sm"><strong>{{ $damage->severity }}</strong>: {{ $damage->description }}</div>
        @empty <p class="text-slate-500">Keine Schäden dokumentiert.</p> @endforelse
    </section>
</div>
@endsection
