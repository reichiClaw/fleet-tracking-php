@extends('layouts.app')

@section('content')
<h1 class="mb-4 text-2xl font-bold">Hersteller-Auschecken: {{ $vehicle->inventory_number }}</h1>
@include('vehicles.partials.inspection-form', ['action' => route('vehicles.manufacturer-checkout', $vehicle), 'submit' => 'An Hersteller auschecken', 'vehicle' => $vehicle, 'showPartner' => true])
@endsection
