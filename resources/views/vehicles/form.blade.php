@extends('layouts.app')

@section('content')
<h1 class="mb-4 text-2xl font-bold">{{ $vehicle->exists ? 'Fahrzeug bearbeiten' : 'Fahrzeug anlegen' }}</h1>
<form method="post" action="{{ $vehicle->exists ? route('vehicles.update', $vehicle) : route('vehicles.store') }}" class="grid gap-4 rounded bg-white p-4 shadow md:grid-cols-2">
    @csrf
    @if($vehicle->exists) @method('PUT') @endif
    <label>Inventarnummer<input name="inventory_number" value="{{ old('inventory_number', $vehicle->inventory_number) }}" required class="mt-1 w-full rounded border p-2"></label>
    <label>Kategorie<select name="vehicle_category_id" required class="mt-1 w-full rounded border p-2">@foreach($categories as $category)<option value="{{ $category->id }}" @selected(old('vehicle_category_id', $vehicle->vehicle_category_id) == $category->id)>{{ $category->name }}</option>@endforeach</select></label>
    <label>Hersteller<input name="manufacturer" value="{{ old('manufacturer', $vehicle->manufacturer) }}" class="mt-1 w-full rounded border p-2"></label>
    <label>Modell<input name="model" value="{{ old('model', $vehicle->model) }}" required class="mt-1 w-full rounded border p-2"></label>
    <label>Seriennummer<input name="serial_number" value="{{ old('serial_number', $vehicle->serial_number) }}" class="mt-1 w-full rounded border p-2"></label>
    <label>Kennzeichen<input name="license_plate" value="{{ old('license_plate', $vehicle->license_plate) }}" class="mt-1 w-full rounded border p-2"></label>
    <label>Baujahr<input name="year" type="number" value="{{ old('year', $vehicle->year) }}" class="mt-1 w-full rounded border p-2"></label>
    <label>Standort<input name="location" value="{{ old('location', $vehicle->location) }}" class="mt-1 w-full rounded border p-2"></label>
    <label>KM-Stand<input name="current_km" type="number" value="{{ old('current_km', $vehicle->current_km ?? 0) }}" required class="mt-1 w-full rounded border p-2"></label>
    <label>Betriebsstunden<input name="current_operating_hours" type="number" step="0.1" value="{{ old('current_operating_hours', $vehicle->current_operating_hours ?? 0) }}" required class="mt-1 w-full rounded border p-2"></label>
    <label>Status<select name="status" required class="mt-1 w-full rounded border p-2">@foreach(\App\Models\Vehicle::STATUSES as $key => $label)<option value="{{ $key }}" @selected(old('status', $vehicle->status ?? 'available') === $key)>{{ $label }}</option>@endforeach</select></label>
    <label class="flex items-center gap-2 pt-7"><input type="checkbox" name="is_active" value="1" @checked(old('is_active', $vehicle->is_active ?? true))> Aktiv</label>
    <label class="md:col-span-2">Notizen<textarea name="notes" class="mt-1 w-full rounded border p-2">{{ old('notes', $vehicle->notes) }}</textarea></label>
    <div class="md:col-span-2"><button class="rounded bg-slate-900 px-4 py-2 text-white">Speichern</button></div>
</form>
@endsection
