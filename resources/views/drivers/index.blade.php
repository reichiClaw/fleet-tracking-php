@extends('layouts.app')
@section('content')
<div class="mb-4 flex justify-between"><h1 class="text-2xl font-bold">Fahrer</h1><a href="{{ route('drivers.create') }}" class="rounded bg-slate-900 px-4 py-2 text-white">Fahrer anlegen</a></div>
<div class="rounded bg-white p-4 shadow">@foreach($drivers as $driver)<div class="border-b py-2"><a class="font-semibold text-blue-700" href="{{ route('drivers.edit', $driver) }}">{{ $driver->name }}</a><br><span class="text-sm text-slate-500">{{ $driver->company }} · {{ $driver->phone }}</span></div>@endforeach</div><div class="mt-4">{{ $drivers->links() }}</div>
@endsection
