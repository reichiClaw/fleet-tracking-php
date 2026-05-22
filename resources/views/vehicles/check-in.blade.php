@extends('layouts.app')

@section('content')
<h1 class="mb-4 text-2xl font-bold">Check-in: {{ $vehicle->inventory_number }}</h1>
@include('vehicles.partials.inspection-form', ['action' => route('vehicles.check-in', $vehicle), 'submit' => 'Check-in speichern', 'vehicle' => $vehicle, 'showPartner' => true])
@endsection
