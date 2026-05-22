@extends('layouts.app')
@section('content')
<h1 class="mb-4 text-2xl font-bold">Kategorien</h1>
<form method="post" action="{{ route('categories.store') }}" class="mb-4 grid gap-3 rounded bg-white p-4 shadow md:grid-cols-4">@csrf<input name="name" placeholder="Name" required class="rounded border p-2"><input name="sort_order" type="number" placeholder="Sortierung" class="rounded border p-2"><input name="description" placeholder="Beschreibung" class="rounded border p-2"><button class="rounded bg-slate-900 px-4 py-2 text-white">Anlegen</button></form>
<div class="rounded bg-white p-4 shadow">@foreach($categories as $category)<div class="border-b py-2"><strong>{{ $category->name }}</strong><span class="ml-2 text-sm text-slate-500">{{ $category->description }}</span></div>@endforeach</div>
@endsection
