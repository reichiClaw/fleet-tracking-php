@extends('layouts.app')

@section('content')
<h1 class="mb-4 text-2xl font-bold">Fahrzeug verleihen</h1>
<form method="post" action="{{ route('loans.store') }}" enctype="multipart/form-data" class="grid gap-4 rounded bg-white p-4 shadow md:grid-cols-2">
    @csrf
    <label>Fahrzeug<select name="vehicle_id" required class="mt-1 w-full rounded border p-2">@foreach($vehicles as $item)<option value="{{ $item->id }}" @selected(optional($vehicle)->id === $item->id)>{{ $item->inventory_number }} · {{ $item->manufacturer }} {{ $item->model }}</option>@endforeach</select></label>
    <label>Typ<select name="borrower_type" class="mt-1 w-full rounded border p-2"><option value="external_company">Subfirma</option><option value="internal_driver">Interner Fahrer</option></select></label>
    <label>Interner Fahrer<select name="driver_id" class="mt-1 w-full rounded border p-2"><option value="">-- optional --</option>@foreach($drivers as $driver)<option value="{{ $driver->id }}">{{ $driver->name }} · {{ $driver->company }}</option>@endforeach</select></label>
    <label>Subfirma<input name="company_name" value="{{ old('company_name') }}" class="mt-1 w-full rounded border p-2"></label>
    <label>Name<input name="borrower_name" value="{{ old('borrower_name') }}" required class="mt-1 w-full rounded border p-2"></label>
    <label>Telefon<input name="phone" value="{{ old('phone') }}" class="mt-1 w-full rounded border p-2"></label>
    <label>Geplante Rueckgabe<input name="planned_return_at" type="datetime-local" required class="mt-1 w-full rounded border p-2"></label>
    <label>KM-Stand<input name="km" type="number" value="{{ old('km', optional($vehicle)->current_km) }}" required class="mt-1 w-full rounded border p-2"></label>
    <label>Betriebsstunden<input name="operating_hours" type="number" step="0.1" value="{{ old('operating_hours', optional($vehicle)->current_operating_hours) }}" required class="mt-1 w-full rounded border p-2"></label>
    <label>Fotos<input name="photos[]" type="file" accept="image/*" multiple class="mt-1 w-full rounded border p-2"></label>
    <label class="md:col-span-2">Bemerkungen / Zustand<textarea name="condition_notes" class="mt-1 w-full rounded border p-2">{{ old('condition_notes') }}</textarea></label>
    <label class="md:col-span-2">Schadenbeschreibung<textarea name="damage_description" class="mt-1 w-full rounded border p-2">{{ old('damage_description') }}</textarea></label>
    <div class="md:col-span-2">
        <label class="mb-1 block font-medium">Unterschrift</label>
        <canvas id="signature" width="600" height="180" class="w-full rounded border bg-white"></canvas>
        <input type="hidden" name="signature_data" id="signature_data">
        <button type="button" onclick="clearSignature()" class="mt-2 rounded bg-slate-200 px-3 py-1">Loeschen</button>
    </div>
    <div class="md:col-span-2"><button onclick="saveSignature()" class="rounded bg-emerald-600 px-4 py-2 text-white">Verleih speichern</button></div>
</form>
<script>
const canvas = document.getElementById('signature'); const ctx = canvas.getContext('2d'); let drawing = false;
function point(e){ const r=canvas.getBoundingClientRect(); const t=e.touches?e.touches[0]:e; return {x:(t.clientX-r.left)*(canvas.width/r.width), y:(t.clientY-r.top)*(canvas.height/r.height)}; }
function start(e){ drawing=true; const p=point(e); ctx.beginPath(); ctx.moveTo(p.x,p.y); e.preventDefault(); }
function move(e){ if(!drawing)return; const p=point(e); ctx.lineTo(p.x,p.y); ctx.lineWidth=3; ctx.lineCap='round'; ctx.stroke(); e.preventDefault(); }
function end(){ drawing=false; }
function clearSignature(){ ctx.clearRect(0,0,canvas.width,canvas.height); }
function saveSignature(){ document.getElementById('signature_data').value = canvas.toDataURL('image/png'); }
canvas.addEventListener('mousedown', start); canvas.addEventListener('mousemove', move); canvas.addEventListener('mouseup', end); canvas.addEventListener('mouseleave', end); canvas.addEventListener('touchstart', start); canvas.addEventListener('touchmove', move); canvas.addEventListener('touchend', end);
</script>
@endsection
