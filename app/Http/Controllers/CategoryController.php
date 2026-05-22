<?php

namespace App\Http\Controllers;

use App\Models\VehicleCategory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CategoryController extends Controller
{
    public function index(): View
    {
        return view('categories.index', ['categories' => VehicleCategory::orderBy('sort_order')->orderBy('name')->get()]);
    }

    public function store(Request $request): RedirectResponse
    {
        VehicleCategory::create($request->validate([
            'name' => ['required', 'string', 'max:120', 'unique:vehicle_categories,name'],
            'description' => ['nullable', 'string'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['sometimes', 'boolean'],
        ]));

        return back()->with('status', 'Kategorie wurde angelegt.');
    }
}
