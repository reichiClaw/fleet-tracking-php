@extends('layouts.app')

@section('content')
<h1 class="mb-4 text-2xl font-bold">Excel-Import</h1>
<form method="post" action="{{ route('vehicles.import') }}" enctype="multipart/form-data" class="rounded bg-white p-4 shadow">
    @csrf
    <p class="mb-3 text-sm text-slate-600">Erwartete Spalten: inventory_number, category, manufacturer, model, serial_number, license_plate, year, location, current_km, current_operating_hours.</p>
    <input name="file" type="file" required accept=".xlsx,.xls,.csv,.txt" class="mb-4 block w-full rounded border p-2">
    <button class="rounded bg-slate-900 px-4 py-2 text-white">Importieren</button>
</form>
@if(session('import_errors'))
    <div class="mt-4 rounded bg-red-50 p-3 text-red-800"><ul class="list-disc pl-5">@foreach(session('import_errors') as $error)<li>{{ $error }}</li>@endforeach</ul></div>
@endif
@endsection
