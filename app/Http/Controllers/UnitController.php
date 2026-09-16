<?php

namespace App\Http\Controllers;

use App\Models\Unit;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class UnitController extends Controller
{
    /**
     * Display a listing of units.
     */
    public function index()
    {
        $units = Unit::latest()->paginate(10);

        return view('units.index', compact('units'));
    }

    /**
     * Show the form for creating a new unit.
     */
    public function create()
    {
        return view('units.create');
    }

    /**
     * Store a newly created unit.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'code' => [
                'required',
                'string',
                'max:20',
                'unique:units,code',
            ],

            'name' => [
                'required',
                'string',
                'max:50',
            ],

            'decimal_places' => [
                'required',
                'integer',
                'min:0',
                'max:6',
            ],

            'status' => [
                'nullable',
                'boolean',
            ],
        ]);

        $validated['status'] = $request->has('status');

        Unit::create($validated);

        return redirect()
            ->route('units.index')
            ->with('success', 'Unit created successfully.');
    }

    /**
     * Show the form for editing a unit.
     */
    public function edit(Unit $unit)
    {
        return view('units.edit', compact('unit'));
    }

    /**
     * Update the specified unit.
     */
    public function update(Request $request, Unit $unit)
    {
        $validated = $request->validate([
            'code' => [
                'required',
                'string',
                'max:20',
                Rule::unique('units', 'code')
                    ->ignore($unit->id),
            ],

            'name' => [
                'required',
                'string',
                'max:50',
            ],

            'decimal_places' => [
                'required',
                'integer',
                'min:0',
                'max:6',
            ],

            'status' => [
                'nullable',
                'boolean',
            ],
        ]);

        $validated['status'] = $request->has('status');

        $unit->update($validated);

        return redirect()
            ->route('units.index')
            ->with('success', 'Unit updated successfully.');
    }

    /**
     * Remove the specified unit.
     */
    public function destroy(Unit $unit)
    {
        try {

            $unit->delete();

            return redirect()
                ->route('units.index')
                ->with('success', 'Unit deleted successfully.');

        } catch (\Illuminate\Database\QueryException $e) {

            return redirect()
                ->route('units.index')
                ->with(
                    'error',
                    'This unit cannot be deleted because it is being used by one or more items.'
                );
        }
    }
}
