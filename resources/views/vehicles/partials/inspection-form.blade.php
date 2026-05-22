<form method="post" action="{{ $action }}" enctype="multipart/form-data" class="grid gap-4 rounded bg-white p-4 shadow md:grid-cols-2">
    @csrf
    <label>KM-Stand<input name="km" type="number" min="0" value="{{ old('km', $vehicle->current_km) }}" required class="mt-1 w-full rounded border p-2"></label>
    <label>Betriebsstunden<input name="operating_hours" type="number" step="0.1" min="0" value="{{ old('operating_hours', $vehicle->current_operating_hours) }}" required class="mt-1 w-full rounded border p-2"></label>
    <label>Standort<input name="location" value="{{ old('location', $vehicle->location) }}" class="mt-1 w-full rounded border p-2"></label>
    @if($showPartner ?? false)<label>Hersteller/Lieferant<input name="external_partner" value="{{ old('external_partner') }}" class="mt-1 w-full rounded border p-2"></label>@endif
    <label class="md:col-span-2">Zustand / Bemerkungen<textarea name="condition_notes" class="mt-1 w-full rounded border p-2">{{ old('condition_notes') }}</textarea></label>
    <label class="md:col-span-2">Schadenbeschreibung<textarea name="damage_description" class="mt-1 w-full rounded border p-2">{{ old('damage_description') }}</textarea></label>
    <label>Schweregrad<select name="damage_severity" class="mt-1 w-full rounded border p-2"><option value="minor">Leicht</option><option value="moderate">Mittel</option><option value="major">Schwer</option><option value="critical">Kritisch</option></select></label>
    <label>Fotos<input name="photos[]" type="file" accept="image/*" multiple class="mt-1 w-full rounded border p-2"></label>
    <div class="md:col-span-2"><button class="rounded bg-slate-900 px-4 py-2 text-white">{{ $submit }}</button></div>
</form>
