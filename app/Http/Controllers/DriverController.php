<?php

namespace App\Http\Controllers;

use App\Models\Driver;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class DriverController extends Controller
{
    public function index(): View
    {
        return view('drivers.index', ['drivers' => Driver::orderBy('name')->paginate(30)]);
    }

    public function create(): View
    {
        return view('drivers.form', ['driver' => new Driver()]);
    }

    public function store(Request $request): RedirectResponse
    {
        Driver::create($this->validateDriver($request));
        return redirect()->route('drivers.index')->with('status', 'Fahrer wurde angelegt.');
    }

    public function edit(Driver $driver): View
    {
        return view('drivers.form', ['driver' => $driver]);
    }

    public function update(Request $request, Driver $driver): RedirectResponse
    {
        $driver->update($this->validateDriver($request, $driver));
        return redirect()->route('drivers.index')->with('status', 'Fahrer wurde aktualisiert.');
    }

    private function validateDriver(Request $request, ?Driver $driver = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:160'],
            'company' => ['nullable', 'string', 'max:160'],
            'department' => ['nullable', 'string', 'max:160'],
            'phone' => ['nullable', 'string', 'max:80'],
            'email' => ['nullable', 'email', 'max:160', Rule::unique('drivers')->ignore($driver)],
            'notes' => ['nullable', 'string'],
            'is_active' => ['sometimes', 'boolean'],
        ]);
    }
}
