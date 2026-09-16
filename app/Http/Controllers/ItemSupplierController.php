<?php

namespace App\Http\Controllers;

use App\Models\Item;
use App\Models\ItemSupplier;
use App\Models\Supplier;
use Illuminate\Http\Request;

class ItemSupplierController extends Controller
{
    /**
     * Display suppliers for an item.
     */
    public function index(Item $item)
    {
        $item->load([
            'suppliers' => function ($query) {
                $query->orderBy('name');
            }
        ]);

        $suppliers = Supplier::where('status', true)
            ->orderBy('name')
            ->get();

        return view('item_suppliers.index', compact(
            'item',
            'suppliers'
        ));
    }


    /**
     * Store a supplier for an item.
     */
    public function store(Request $request, Item $item)
{
    /*
    |--------------------------------------------------------------------------
    | Validate
    |--------------------------------------------------------------------------
    */

    $validated = $request->validate([

        'supplier_id' => [
            'required',
            'exists:suppliers,id',
        ],

        'supplier_item_code' => [
            'nullable',
            'string',
            'max:100',
        ],

        'unit_price' => [
            'nullable',
            'numeric',
            'min:0',
        ],

        'lead_time_days' => [
            'nullable',
            'integer',
            'min:0',
        ],

        'minimum_order_qty' => [
            'nullable',
            'numeric',
            'min:0',
        ],

        'is_preferred' => [
            'nullable',
            'boolean',
        ],

        'status' => [
            'nullable',
            'boolean',
        ],

    ]);


    /*
    |--------------------------------------------------------------------------
    | Prevent Duplicate Supplier
    |--------------------------------------------------------------------------
    */

    $alreadyExists = ItemSupplier::where('item_id', $item->id)
        ->where('supplier_id', $validated['supplier_id'])
        ->exists();

    if ($alreadyExists) {

        return redirect()
            ->route('items.suppliers.index', $item)
            ->with(
                'error',
                'This supplier is already assigned to this item.'
            );
    }


    /*
    |--------------------------------------------------------------------------
    | Handle Checkboxes
    |--------------------------------------------------------------------------
    */

    $validated['item_id'] = $item->id;

    $validated['is_preferred'] =
        $request->has('is_preferred');

    $validated['status'] =
        $request->has('status');


    /*
    |--------------------------------------------------------------------------
    | Preferred Supplier Must Be Active
    |--------------------------------------------------------------------------
    |
    | An inactive supplier cannot be used as the preferred supplier
    | for purchase planning.
    |
    */

    if (
        $validated['is_preferred']
        &&
        !$validated['status']
    ) {

        return redirect()
            ->back()
            ->withInput()
            ->with(
                'error',
                'A preferred supplier must be active.'
            );
    }


    /*
    |--------------------------------------------------------------------------
    | Preferred Supplier Logic
    |--------------------------------------------------------------------------
    |
    | Only one ACTIVE preferred supplier is allowed for each item.
    |
    | If the new supplier is marked as preferred, remove Preferred
    | from all other suppliers of this item.
    |
    */

    if (
        $validated['is_preferred']
        &&
        $validated['status']
    ) {

        ItemSupplier::where('item_id', $item->id)
            ->where('is_preferred', true)
            ->update([
                'is_preferred' => false,
            ]);
    }


    /*
    |--------------------------------------------------------------------------
    | Create Item Supplier
    |--------------------------------------------------------------------------
    */

    ItemSupplier::create($validated);


    /*
    |--------------------------------------------------------------------------
    | Redirect
    |--------------------------------------------------------------------------
    */

    return redirect()
        ->route('items.suppliers.index', $item)
        ->with(
            'success',
            $validated['is_preferred']
                ? 'Supplier added and set as the preferred supplier successfully.'
                : 'Supplier added to item successfully.'
        );
}


    /**
     * Show the form for editing an item supplier.
     */
    public function edit(Item $item, ItemSupplier $itemSupplier)
    {
        // Make sure this supplier actually belongs to this item.
        abort_unless(
            $itemSupplier->item_id === $item->id,
            404
        );

        $suppliers = Supplier::where('status', true)
            ->orderBy('name')
            ->get();

        return view(
            'item_suppliers.edit',
            compact(
                'item',
                'itemSupplier',
                'suppliers'
            )
        );
    }

    /**
     * Update an item supplier.
     */
    public function update(
    Request $request,
    Item $item,
    ItemSupplier $itemSupplier
) {
    /*
    |--------------------------------------------------------------------------
    | Make Sure The Record Belongs To This Item
    |--------------------------------------------------------------------------
    */

    abort_unless(
        $itemSupplier->item_id === $item->id,
        404
    );


    /*
    |--------------------------------------------------------------------------
    | Validate
    |--------------------------------------------------------------------------
    */

    $validated = $request->validate([

        'supplier_id' => [
            'required',
            'exists:suppliers,id',
        ],

        'supplier_item_code' => [
            'nullable',
            'string',
            'max:100',
        ],

        'unit_price' => [
            'nullable',
            'numeric',
            'min:0',
        ],

        'lead_time_days' => [
            'nullable',
            'integer',
            'min:0',
        ],

        'minimum_order_qty' => [
            'nullable',
            'numeric',
            'min:0',
        ],

        'is_preferred' => [
            'nullable',
            'boolean',
        ],

        'status' => [
            'nullable',
            'boolean',
        ],

    ]);


    /*
    |--------------------------------------------------------------------------
    | Prevent Duplicate Supplier
    |--------------------------------------------------------------------------
    |
    | If the supplier is changed, make sure that supplier is not
    | already assigned to this item through another record.
    |
    */

    $alreadyExists = ItemSupplier::where('item_id', $item->id)
        ->where('supplier_id', $validated['supplier_id'])
        ->where('id', '!=', $itemSupplier->id)
        ->exists();

    if ($alreadyExists) {

        return redirect()
            ->back()
            ->withInput()
            ->with(
                'error',
                'This supplier is already assigned to this item.'
            );
    }


    /*
    |--------------------------------------------------------------------------
    | Handle Checkboxes
    |--------------------------------------------------------------------------
    */

    $validated['is_preferred'] =
        $request->has('is_preferred');

    $validated['status'] =
        $request->has('status');


    /*
    |--------------------------------------------------------------------------
    | Preferred Supplier Must Be Active
    |--------------------------------------------------------------------------
    |
    | Do not allow:
    |
    | Preferred = Yes
    | Active    = No
    |
    */

    if (
        $validated['is_preferred']
        &&
        !$validated['status']
    ) {

        return redirect()
            ->back()
            ->withInput()
            ->with(
                'error',
                'A preferred supplier must be active.'
            );
    }


    /*
    |--------------------------------------------------------------------------
    | Preferred Supplier Logic
    |--------------------------------------------------------------------------
    |
    | Only one ACTIVE preferred supplier is allowed for each item.
    |
    | When this supplier becomes preferred, remove Preferred from
    | every other supplier assigned to the same item.
    |
    */

    if (
        $validated['is_preferred']
        &&
        $validated['status']
    ) {

        ItemSupplier::where('item_id', $item->id)
            ->where('id', '!=', $itemSupplier->id)
            ->where('is_preferred', true)
            ->update([
                'is_preferred' => false,
            ]);
    }


    /*
    |--------------------------------------------------------------------------
    | Update
    |--------------------------------------------------------------------------
    */

    $itemSupplier->update($validated);


    /*
    |--------------------------------------------------------------------------
    | Redirect
    |--------------------------------------------------------------------------
    */

    return redirect()
        ->route('items.suppliers.index', $item)
        ->with(
            'success',
            $validated['is_preferred']
                ? 'updated and set as the preferred supplier successfully.'
                : 'Item supplier updated successfully.'
        );
}


    /**
     * Remove a supplier from an item.
     */
    public function destroy(
        Item $item,
        ItemSupplier $itemSupplier
    ) {
        /*
        |--------------------------------------------------------------------------
        | Make sure the record belongs to this item
        |--------------------------------------------------------------------------
        */

        abort_unless(
            $itemSupplier->item_id === $item->id,
            404
        );


        /*
        |--------------------------------------------------------------------------
        | Delete
        |--------------------------------------------------------------------------
        */

        $itemSupplier->delete();


        /*
        |--------------------------------------------------------------------------
        | Redirect
        |--------------------------------------------------------------------------
        */

        return redirect()
            ->route('items.suppliers.index', $item)
            ->with(
                'success',
                'Supplier removed from item successfully.'
            );
    }
}
