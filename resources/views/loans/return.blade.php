@extends('layouts.app')

@section('content')
<h1 class="mb-4 text-2xl font-bold">Rueckgabe: {{ $loan->vehicle->inventory_number }}</h1>
<form method="post" action="{{ route('loans.return', $loan) }}" enctype="multipart/form-data" class="grid gap-4 rounded bg-white p-4 shadow md:grid-cols-2">
    @csrf
    <label>KM-Stand<input name="km" type="number" value="{{ old('km', $loan->vehicle->current_km) }}" required class="mt-1 w-full rounded border p-2"></label>
    <label>Betriebsstunden<input name="operating_hours" type="number" step="0.1" value="{{ old('operating_hours', $loan->vehicle->current_operating_hours) }}" required class="mt-1 w-full rounded border p-2"></label>
    <label>Standort<input name="location" value="{{ old('location', $loan->vehicle->location) }}" class="mt-1 w-full rounded border p-2"></label>
    <label>Fotos<input name="photos[]" type="file" accept="image/*" multiple class="mt-1 w-full rounded border p-2"></label>
    <label class="md:col-span-2">Zustand / Bemerkungen<textarea name="condition_notes" class="mt-1 w-full rounded border p-2">{{ old('condition_notes') }}</textarea></label>
    <label class="md:col-span-2">Neue Schäden<textarea name="damage_description" class="mt-1 w-full rounded border p-2">{{ old('damage_description') }}</textarea></label>
    <label>Schweregrad<select name="damage_severity" class="mt-1 w-full rounded border p-2"><option value="minor">Leicht</option><option value="moderate">Mittel</option><option value="major">Schwer</option><option value="critical">Kritisch</option></select></label>
    <div class="md:col-span-2"><button class="rounded bg-emerald-600 px-4 py-2 text-white">Rueckgabe speichern</button></div>
</form>
@endsection
