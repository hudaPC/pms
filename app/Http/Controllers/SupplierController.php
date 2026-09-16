<?php

namespace App\Http\Controllers;


use App\Models\Supplier;

use Illuminate\Http\Request;

class SupplierController extends Controller
{
    /**
     * Display a listing of suppliers.
     */
    public function index()
    {
        $suppliers = Supplier::latest()->paginate(10);

        return view('suppliers.index', compact('suppliers'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $lastSupplier = Supplier::latest('id')->first();

        if ($lastSupplier) {

            $lastNumber = (int) str_replace('SUP', '', $lastSupplier->code);

            $nextNumber = $lastNumber + 1;
        } else {

            $nextNumber = 1;
        }

        $nextCode = 'SUP' . str_pad($nextNumber, 5, '0', STR_PAD_LEFT);

        return view('suppliers.create', compact('nextCode'));
    }
    /**
     * Store a newly created resource in storage.
     */

    public function store(Request $request)
    {
        /*
    |--------------------------------------------------------------------------
    | Validate Supplier Information
    |--------------------------------------------------------------------------
    */

        $validated = $request->validate([

            'name' => [
                'required',
                'string',
                'max:150',
            ],

            'contact_person' => [
                'nullable',
                'string',
                'max:100',
            ],

            'phone' => [
                'nullable',
                'string',
                'max:30',
            ],

            'email' => [
                'nullable',
                'email',
                'max:150',
            ],

            'address' => [
                'nullable',
                'string',
            ],

            'payment_terms' => [
                'nullable',
                'string',
                'max:100',
            ],

            'default_lead_time_days' => [
                'nullable',
                'integer',
                'min:0',
            ],

            'status' => [
                'nullable',
                'boolean',
            ],

            'remarks' => [
                'nullable',
                'string',
            ],

        ]);


        /*
    |--------------------------------------------------------------------------
    | Generate Supplier Code Automatically
    |--------------------------------------------------------------------------
    */

        $lastSupplier = Supplier::latest('id')->first();

        if ($lastSupplier) {

            $lastNumber = (int) str_replace(
                'SUP',
                '',
                $lastSupplier->code
            );

            $nextNumber = $lastNumber + 1;
        } else {

            $nextNumber = 1;
        }


        $validated['code'] = 'SUP' . str_pad(
            $nextNumber,
            5,
            '0',
            STR_PAD_LEFT
        );


        /*
    |--------------------------------------------------------------------------
    | Handle Status Checkbox
    |--------------------------------------------------------------------------
    */

        $validated['status'] = $request->has('status');


        /*
    |--------------------------------------------------------------------------
    | Create Supplier
    |--------------------------------------------------------------------------
    */

        Supplier::create($validated);


        /*
    |--------------------------------------------------------------------------
    | Redirect
    |--------------------------------------------------------------------------
    */

        return redirect()
            ->route('suppliers.index')
            ->with(
                'success',
                'Supplier created successfully.'
            );
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    /**
     * Show the form for editing the specified supplier.
     */
    public function edit(Supplier $supplier)
    {
        return view('suppliers.edit', compact('supplier'));
    }

    /**
     * Update the specified resource in storage.
     */
    /**
     * Update the specified supplier.
     */
    public function update(Request $request, Supplier $supplier)
    {
        /*
    |--------------------------------------------------------------------------
    | Validate Supplier Information
    |--------------------------------------------------------------------------
    */

        $validated = $request->validate([

            'name' => [
                'required',
                'string',
                'max:150',
            ],

            'contact_person' => [
                'nullable',
                'string',
                'max:100',
            ],

            'phone' => [
                'nullable',
                'string',
                'max:30',
            ],

            'email' => [
                'nullable',
                'email',
                'max:150',
            ],

            'address' => [
                'nullable',
                'string',
            ],

            'payment_terms' => [
                'nullable',
                'string',
                'max:100',
            ],

            'default_lead_time_days' => [
                'nullable',
                'integer',
                'min:0',
            ],

            'status' => [
                'nullable',
                'boolean',
            ],

            'remarks' => [
                'nullable',
                'string',
            ],

        ]);


        /*
    |--------------------------------------------------------------------------
    | Handle Status Checkbox
    |--------------------------------------------------------------------------
    */

        $validated['status'] = $request->has('status');


        /*
    |--------------------------------------------------------------------------
    | Update Supplier
    |--------------------------------------------------------------------------
    */

        $supplier->update($validated);


        /*
    |--------------------------------------------------------------------------
    | Redirect
    |--------------------------------------------------------------------------
    */

        return redirect()
            ->route('suppliers.index')
            ->with(
                'success',
                'Supplier updated successfully.'
            );
    }

    /**
     * Remove the specified resource from storage.
     */
    /**
     * Remove the specified supplier.
     */
    public function destroy(Supplier $supplier)
    {
        try {

            $supplier->delete();

            return redirect()
                ->route('suppliers.index')
                ->with(
                    'success',
                    'Supplier deleted successfully.'
                );
        } catch (\Illuminate\Database\QueryException $e) {

            return redirect()
                ->route('suppliers.index')
                ->with(
                    'error',
                    'This supplier cannot be deleted because it is being used by one or more items.'
                );
        }
    }
}
