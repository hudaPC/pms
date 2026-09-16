<?php

namespace App\Http\Controllers;

use App\Models\Item;
use App\Models\ProductModel;
use App\Models\ProductModelItem;
use Illuminate\Http\Request;

class ProductModelItemController extends Controller
{
    /**
     * Display BOM / component items for a product model.
     */
    public function index(ProductModel $productModel)
    {
        $modelItems = $productModel->modelItems()
            ->with('item')
            // ->orderBy('line_no')
            ->paginate(20);

        return view(
            'product-models.items.index',
            compact(
                'productModel',
                'modelItems'
            )
        );
    }


    /**
     * Show form for adding an item to a product model.
     */
    public function create(ProductModel $productModel)
    {
        /*
        |--------------------------------------------------------------------------
        | Items Already Added To THIS Product Model
        |--------------------------------------------------------------------------
        */

        $existingItems = $productModel->modelItems()
            ->with('item')
            ->get();

        $existingItemIds = $existingItems
            ->pluck('item_id')
            ->filter()
            ->values()
            ->toArray();


        /*
        |--------------------------------------------------------------------------
        | Detect Current Product Model Item Family
        |--------------------------------------------------------------------------
        |
        | Example:
        |
        | Document No. : PL-M0029X-01
        | Item Family  : M0029
        |
        */

        $itemFamily = null;

        if (
            preg_match(
                '/M\d{4}/i',
                $productModel->document_no,
                $matches
            )
        ) {
            $itemFamily = strtoupper($matches[0]);
        }


        /*
        |--------------------------------------------------------------------------
        | Items Already Added To Current Model
        |--------------------------------------------------------------------------
        */

        $usedItemIds = ProductModelItem::where(
            'product_model_id',
            $productModel->id
        )
            ->pluck('item_id');


        /*
        |--------------------------------------------------------------------------
        | Available Items
        |--------------------------------------------------------------------------
        |
        | Show:
        |
        | 1. Current Product Model family items
        |    Example: M0029A-M001, M0029L-G001, M0029R-G002
        |
        | 2. Items that are NOT assigned to any Product Model yet
        |    Example: CR05, CR06, CR07, IP02501, N331-B, etc.
        |
        | Hide:
        |
        | 1. Items already added to current Product Model
        | 2. Items belonging to another Product Model
        | 3. M0026... when current model is M0029...
        | 4. CIMR... if it already belongs to another Product Model
        |
        */

        $items = Item::query()

            ->whereNotIn('id', $existingItemIds)

            ->where(function ($query) use ($productModel) {

                $query->where(
                    'product_model_id',
                    $productModel->id
                )
                    ->orWhereNull('product_model_id');

            })

            ->where('status', true)

            ->orderBy('item_code')

            ->get();


        /*
        |--------------------------------------------------------------------------
        | Latest Old Part No. / Revision
        |--------------------------------------------------------------------------
        */

        $lastUsage = ProductModelItem::whereNotNull('old_part_no')
            ->orderByDesc('created_at')
            ->get([
                'item_id',
                'old_part_no',
                'revision',
            ])
            ->unique('item_id')
            ->keyBy('item_id');


        /*
        |--------------------------------------------------------------------------
        | Return View
        |--------------------------------------------------------------------------
        */

        return view(
            'product-models.items.create',
            compact(
                'productModel',
                'items',
                'lastUsage',
                'existingItems',
                'existingItemIds'
            )
        );
    }
    /**
     * Store a component item.
     */
    public function store(
        Request $request,
        ProductModel $productModel
    ) {
        $validated = $request->validate([
            'item_id' => [
                'required',
                'exists:items,id',
            ],

            'line_no' => [
                'nullable',
                'integer',
                'min:1',
            ],

            'old_part_no' => [
                'nullable',
                'string',
                'max:100',
            ],

            'revision' => [
                'nullable',
                'string',
                'max:50',
            ],

            'quantity' => [
                'required',
                'numeric',
            ],

            'remarks' => [
                'nullable',
                'string',
            ],
        ]);


        /*
        |--------------------------------------------------------------------------
        | Prevent duplicate item in same Product Model
        |--------------------------------------------------------------------------
        */

        $exists = ProductModelItem::where(
            'product_model_id',
            $productModel->id
        )
            ->where(
                'item_id',
                $validated['item_id']
            )
            ->exists();


        if ($exists) {

            return back()
                ->withInput()
                ->withErrors([
                    'item_id' =>
                        'This item already exists in this product model.',
                ]);
        }


        /*
        |--------------------------------------------------------------------------
        | Save Product Model Item
        |--------------------------------------------------------------------------
        */

        $validated['product_model_id'] =
            $productModel->id;

        ProductModelItem::create($validated);


        /*
        |--------------------------------------------------------------------------
        | Redirect
        |--------------------------------------------------------------------------
        */

        return redirect()
            ->route(
                'product-models.items.index',
                $productModel
            )
            ->with(
                'success',
                'Product model item added successfully.'
            );
    }


    /**
     * Show edit form.
     */
    public function edit(
        ProductModel $productModel,
        ProductModelItem $productModelItem
    ) {
        abort_if(
            $productModelItem->product_model_id !==
            $productModel->id,
            404
        );

        $items = Item::orderBy('item_code')
            ->get();

        return view(
            'product-models.items.edit',
            compact(
                'productModel',
                'productModelItem',
                'items'
            )
        );
    }


    /**
     * Update component item.
     */
    public function update(
        Request $request,
        ProductModel $productModel,
        ProductModelItem $productModelItem
    ) {
        abort_if(
            $productModelItem->product_model_id !==
            $productModel->id,
            404
        );


        $validated = $request->validate([
            'item_id' => [
                'required',
                'exists:items,id',
            ],

            'line_no' => [
                'nullable',
                'integer',
                'min:1',
            ],

            'old_part_no' => [
                'nullable',
                'string',
                'max:100',
            ],

            'revision' => [
                'nullable',
                'string',
                'max:50',
            ],

            'quantity' => [
                'required',
                'numeric',
            ],


            'remarks' => [
                'nullable',
                'string',
            ],
        ]);


        /*
        |--------------------------------------------------------------------------
        | Prevent duplicate item in same Product Model
        |--------------------------------------------------------------------------
        */

        $exists = ProductModelItem::where(
            'product_model_id',
            $productModel->id
        )
            ->where(
                'item_id',
                $validated['item_id']
            )
            ->where(
                'id',
                '!=',
                $productModelItem->id
            )
            ->exists();


        if ($exists) {

            return back()
                ->withInput()
                ->withErrors([
                    'item_id' =>
                        'This item already exists in this product model.',
                ]);
        }


        /*
        |--------------------------------------------------------------------------
        | Update
        |--------------------------------------------------------------------------
        */

        $productModelItem->update($validated);


        return redirect()
            ->route(
                'product-models.items.index',
                $productModel
            )
            ->with(
                'success',
                'Product model item updated successfully.'
            );
    }


    /**
     * Remove component item.
     */
    public function destroy(
        ProductModel $productModel,
        ProductModelItem $productModelItem
    ) {
        abort_if(
            $productModelItem->product_model_id !==
            $productModel->id,
            404
        );

        $productModelItem->delete();

        return redirect()
            ->route(
                'product-models.items.index',
                $productModel
            )
            ->with(
                'success',
                'Product model item deleted successfully.'
            );
    }
}
