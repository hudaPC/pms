<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Item;
use App\Models\ItemPurchaseSetting;
use App\Models\Supplier;
use App\Models\Unit;
use Illuminate\Http\Request;
use App\Models\ProductModel;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use App\Services\ItemPurchasePlanningService;
use App\Models\StockMovement;


class ItemController extends Controller
{
    /**
     * Display a listing of items.
     */
    public function index(Request $request)
    {
        $query = Item::with([
            'category',
            'unit',
            'defaultSupplier',
        ]);

        /*
    |--------------------------------------------------------------------------
    | Filter By Product Model
    |--------------------------------------------------------------------------
    */

        if ($request->filled('product_model_id')) {

            $query->whereHas('productModelItems', function ($q) use ($request) {
                $q->where('product_model_id', $request->product_model_id);
            });
        }

        /*
    |--------------------------------------------------------------------------
    | Filter By Item Code
    |--------------------------------------------------------------------------
    */

        if ($request->filled('item_id')) {

            $query->where('id', $request->item_id);
        }

        /*
    |--------------------------------------------------------------------------
    | Items
    |--------------------------------------------------------------------------
    */

        $items = $query
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('items.index', compact('items'));
    }


    /**
     * Show the form for creating a new item.
     */
    public function create()
    {
        /*
        |--------------------------------------------------------------------------
        | Categories
        |--------------------------------------------------------------------------
        */

        $categories = Category::where('status', true)
            ->orderBy('name')
            ->get();


        /*
        |--------------------------------------------------------------------------
        | Units
        |--------------------------------------------------------------------------
        */

        $units = Unit::where('status', true)
            ->orderBy('name')
            ->get();


        /*
        |--------------------------------------------------------------------------
        | Suppliers
        |--------------------------------------------------------------------------
        */

        $suppliers = Supplier::where('status', true)
            ->orderBy('name')
            ->get();


        /*
        |--------------------------------------------------------------------------
        | Product Models
        |--------------------------------------------------------------------------
        */

        $productModels = ProductModel::where('status', true)
            ->orderBy('model_code')
            ->get();


        /*
        |--------------------------------------------------------------------------
        | Return View
        |--------------------------------------------------------------------------
        */

        return view('items.create', compact(
            'categories',
            'units',
            'suppliers',
            'productModels'
        ));
    }


    /**
     * Store a newly created item.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            /*
   |--------------------------------------------------------------------------
   | Product Model
   |--------------------------------------------------------------------------
   */

            'product_model_id' => [
                'required',
                'exists:product_models,id',
            ],


            // Item information
            'item_code' => [
                'required',
                'string',
                'max:50',

                Rule::unique('items', 'item_code')
                    ->where(function ($query) use ($request) {
                        return $query->where(
                            'product_model_id',
                            $request->product_model_id
                        );
                    }),
            ],

            'item_name' => [
                'required',
                'string',
                'max:200',
            ],

            'category_id' => [
                'required',
                'exists:categories,id',
            ],

            'unit_id' => [
                'required',
                'exists:units,id',
            ],

            'default_supplier_id' => [
                'nullable',
                'exists:suppliers,id',
            ],

            'description' => [
                'nullable',
                'string',
            ],

            'status' => [
                'nullable',
                'boolean',
            ],


            // Purchase planning settings
            'opening_stock' => [
                'nullable',
                'numeric',
                'min:0',
            ],

            'monthly_usage' => [
                'nullable',
                'numeric',
                'min:0',
            ],

            'lead_time_days' => [
                'nullable',
                'integer',
                'min:0',
            ],

            'safety_stock' => [
                'nullable',
                'numeric',
                'min:0',
            ],

            'order_cycle_days' => [
                'nullable',
                'integer',
                'min:0',
            ],

            'order_quantity' => [
                'nullable',
                'numeric',
                'min:0',
            ],

            'planning_start_date' => [
                'nullable',
                'date',
            ],

            'minimum_stock' => [
                'nullable',
                'numeric',
                'min:0',
            ],

            'maximum_stock' => [
                'nullable',
                'numeric',
                'min:0',
            ],
        ]);


        /*
        |--------------------------------------------------------------------------
        | Prepare checkbox value
        |--------------------------------------------------------------------------
        */

        $validated['status'] = $request->has('status');


        /*
        |--------------------------------------------------------------------------
        | Save Item + Purchase Settings
        |--------------------------------------------------------------------------
        */

        $item = DB::transaction(function () use ($validated) {

            /*
            |--------------------------------------------------------------------------
            | Create Item
            |--------------------------------------------------------------------------
            */

            $item = Item::create([
                'product_model_id' => $validated['product_model_id'],
                'item_code' => $validated['item_code'],
                'item_name' => $validated['item_name'],
                'category_id' => $validated['category_id'],
                'unit_id' => $validated['unit_id'],
                'default_supplier_id' => $validated['default_supplier_id'] ?? null,
                'description' => $validated['description'] ?? null,
                'status' => $validated['status'],
            ]);


            /*
            |--------------------------------------------------------------------------
            | Create Purchase Planning Settings
            |--------------------------------------------------------------------------
            |
            | Monthly Usage, Safety Stock and Planning Start Date are production-driven.
            | They will be calculated after the item is linked to the Product Model BOM
            | and a qualifying Production Plan exists.
            |
            */

            ItemPurchaseSetting::create([

                'item_id' => $item->id,

                /*
    |--------------------------------------------------------------------------
    | Manual / Base Settings
    |--------------------------------------------------------------------------
    */

                'opening_stock' =>
                    $validated['opening_stock'] ?? 0,

                'lead_time_days' =>
                    $validated['lead_time_days'] ?? 60,

                'order_cycle_days' =>
                    $validated['order_cycle_days'] ?? 60,

                'order_quantity' =>
                    $validated['order_quantity'] ?? 0,

                'minimum_stock' =>
                    $validated['minimum_stock'] ?? null,

                'maximum_stock' =>
                    $validated['maximum_stock'] ?? null,


                /*
                |--------------------------------------------------------------------------
                | Production-Driven Settings
                |--------------------------------------------------------------------------
                |
                | These are not manually fixed during initial Item creation.
                |
                */

                'monthly_usage' => 0,

                'safety_stock' => 0,

                'planning_start_date' => null,
            ]);

            /*
            |--------------------------------------------------------------------------
            | Create Opening Stock Movement
            |--------------------------------------------------------------------------
            */

            $openingStock =
                (float) ($validated['opening_stock'] ?? 0);

            if ($openingStock > 0) {

                StockMovement::create([

                    'item_id' =>
                        $item->id,

                    'movement_date' =>
                        now()->toDateString(),

                    'movement_type' =>
                        'opening_stock',

                    'direction' =>
                        'in',

                    'quantity' =>
                        $openingStock,

                    'source_type' =>
                        'item',

                    'source_id' =>
                        $item->id,

                    'reference' =>
                        'Opening Stock',

                    'remarks' =>
                        'Initial opening stock created with item.',
                ]);
            }
            /*
            |--------------------------------------------------------------------------
            | Return Newly Created Item
            |--------------------------------------------------------------------------
            */

            return $item;
        });


        /*
        |--------------------------------------------------------------------------
        | Redirect
        |--------------------------------------------------------------------------
        */

        return redirect()
            ->route(
                'product-models.items.create',
                [
                    'productModel' => $validated['product_model_id'],
                    'item_id' => $item->id,
                ]
            )
            ->with(
                'success',
                'Item created successfully. Now complete the Product Model BOM information.'
            );
    }
    /**
     * Show the form for editing an item.
     */
    public function edit(
        Item $item,
        ItemPurchasePlanningService $itemPurchasePlanningService
    ) {
        /*
    |--------------------------------------------------------------------------
    | Load Purchase Settings
    |--------------------------------------------------------------------------
    */

        $item->load('purchaseSettings');


        /*
    |--------------------------------------------------------------------------
    | Get Current Effective Lead Time
    |--------------------------------------------------------------------------
    */

        $commonPlanning =
            $itemPurchasePlanningService
                ->calculate($item);

        $leadTimeDays =
            $commonPlanning['has_settings']
            ? (int) $commonPlanning['lead_time_days']
            : (int) ($item->purchaseSettings->lead_time_days ?? 60);


        /*
    |--------------------------------------------------------------------------
    | Get Production-Driven Demand
    |--------------------------------------------------------------------------
    */

        $productionDemand =
            $itemPurchasePlanningService
                ->getProductionDemand(
                    $item,
                    $leadTimeDays
                );


        /*
    |--------------------------------------------------------------------------
    | Show Dynamic Values On Edit Screen
    |--------------------------------------------------------------------------
    */

        if (
            $productionDemand['has_plan']
            &&
            $item->purchaseSettings
        ) {

            $item->purchaseSettings->monthly_usage =
                $productionDemand['monthly_usage'];

            $item->purchaseSettings->safety_stock =
                $productionDemand['safety_stock'];

            $item->purchaseSettings->planning_start_date =
                $productionDemand['planning_start_date'];
        }


        /*
    |--------------------------------------------------------------------------
    | Product Models
    |--------------------------------------------------------------------------
    */

        $productModels =
            ProductModel::where('status', true)
                ->orderBy('model_code')
                ->get();


        /*
    |--------------------------------------------------------------------------
    | Categories
    |--------------------------------------------------------------------------
    */

        $categories =
            Category::where('status', true)
                ->orderBy('name')
                ->get();


        /*
    |--------------------------------------------------------------------------
    | Units
    |--------------------------------------------------------------------------
    */

        $units =
            Unit::where('status', true)
                ->orderBy('name')
                ->get();


        /*
    |--------------------------------------------------------------------------
    | Suppliers
    |--------------------------------------------------------------------------
    */

        $suppliers =
            Supplier::where('status', true)
                ->orderBy('name')
                ->get();


        /*
    |--------------------------------------------------------------------------
    | Return View
    |--------------------------------------------------------------------------
    */

        return view('items.edit', compact(
            'item',
            'categories',
            'units',
            'suppliers',
            'productModels'
        ));
    }
    /**
     * Update the specified item.
     */
    public function update(
        Request $request,
        Item $item,
        ItemPurchasePlanningService $itemPurchasePlanningService
    ) {
        /*
        |--------------------------------------------------------------------------
        | Validation
        |--------------------------------------------------------------------------
        */

        $validated = $request->validate([

            'product_model_id' => [
                'nullable',
                'exists:product_models,id',
            ],

            /*
            |--------------------------------------------------------------------------
            | Item Information
            |--------------------------------------------------------------------------
            */

            'item_code' => [
                'required',
                'string',
                'max:50',

                Rule::unique('items', 'item_code')
                    ->where(function ($query) use ($request) {

                        return $query->where(
                            'product_model_id',
                            $request->product_model_id
                        );

                    })
                    ->ignore($item->id),
            ],

            'item_name' => [
                'required',
                'string',
                'max:200',
            ],

            'category_id' => [
                'required',
                'exists:categories,id',
            ],

            'unit_id' => [
                'required',
                'exists:units,id',
            ],

            'default_supplier_id' => [
                'nullable',
                'exists:suppliers,id',
            ],

            'description' => [
                'nullable',
                'string',
            ],

            'status' => [
                'nullable',
                'boolean',
            ],

            /*
            |--------------------------------------------------------------------------
            | Purchase Planning Settings
            |--------------------------------------------------------------------------
            */

            'opening_stock' => [
                'nullable',
                'numeric',
                'min:0',
            ],

            'lead_time_days' => [
                'nullable',
                'integer',
                'min:0',
            ],

            'order_cycle_days' => [
                'nullable',
                'integer',
                'min:0',
            ],

            'order_quantity' => [
                'nullable',
                'numeric',
                'min:0',
            ],

            'minimum_stock' => [
                'nullable',
                'numeric',
                'min:0',
            ],

            'maximum_stock' => [
                'nullable',
                'numeric',
                'min:0',
            ],
        ]);


        /*
        |--------------------------------------------------------------------------
        | Checkbox
        |--------------------------------------------------------------------------
        */

        $validated['status'] =
            $request->has('status');


        /*
        |--------------------------------------------------------------------------
        | Update Item + Purchase Settings + Opening Stock
        |--------------------------------------------------------------------------
        */

        DB::transaction(function () use ($validated, $item, $itemPurchasePlanningService) {

            /*
            |--------------------------------------------------------------------------
            | Update Item
            |--------------------------------------------------------------------------
            */

            $item->update([

                'product_model_id' =>
                    $validated['product_model_id'] ?? null,

                'item_code' =>
                    $validated['item_code'],

                'item_name' =>
                    $validated['item_name'],

                'category_id' =>
                    $validated['category_id'],

                'unit_id' =>
                    $validated['unit_id'],

                'default_supplier_id' =>
                    $validated['default_supplier_id'] ?? null,

                'description' =>
                    $validated['description'] ?? null,

                'status' =>
                    $validated['status'],
            ]);


            /*
            |--------------------------------------------------------------------------
            | Existing Purchase Settings
            |--------------------------------------------------------------------------
            */

            $existingPurchaseSettings =
                $item->purchaseSettings()
                    ->first();


            /*
            |--------------------------------------------------------------------------
            | Save Manual / Base Purchase Settings
            |--------------------------------------------------------------------------
            */

            $purchaseSettings =
                $item->purchaseSettings()
                    ->updateOrCreate(

                        [
                            'item_id' => $item->id,
                        ],

                        [
                            /*
                            |--------------------------------------------------------------------------
                            | Manual / Base Values
                            |--------------------------------------------------------------------------
                            */

                            'opening_stock' =>
                                $validated['opening_stock'] ?? 0,

                            'lead_time_days' =>
                                $validated['lead_time_days'] ?? 60,

                            'order_cycle_days' =>
                                $validated['order_cycle_days'] ?? 60,

                            'order_quantity' =>
                                $validated['order_quantity'] ?? 0,

                            'minimum_stock' =>
                                $validated['minimum_stock'] ?? null,

                            'maximum_stock' =>
                                $validated['maximum_stock'] ?? null,


                            /*
                            |--------------------------------------------------------------------------
                            | Preserve Existing Production-Driven Values
                            |--------------------------------------------------------------------------
                            */

                            'monthly_usage' =>
                                $existingPurchaseSettings
                                ? $existingPurchaseSettings->monthly_usage
                                : 0,

                            'safety_stock' =>
                                $existingPurchaseSettings
                                ? $existingPurchaseSettings->safety_stock
                                : 0,

                            'planning_start_date' =>
                                $existingPurchaseSettings
                                ? $existingPurchaseSettings->planning_start_date
                                : null,
                        ]
                    );


            /*
            |--------------------------------------------------------------------------
            | Reload Purchase Settings
            |--------------------------------------------------------------------------
            */

            $item->unsetRelation('purchaseSettings');

            $item->load('purchaseSettings');


            /*
            |--------------------------------------------------------------------------
            | Calculate Current Effective Planning
            |--------------------------------------------------------------------------
            */

            $commonPlanning =
                $itemPurchasePlanningService
                    ->calculate($item);


            /*
            |--------------------------------------------------------------------------
            | Effective Lead Time
            |--------------------------------------------------------------------------
            */

            $leadTimeDays =
                $commonPlanning['has_settings']
                ? (int) $commonPlanning['lead_time_days']
                : (int) ($purchaseSettings->lead_time_days ?? 60);


            /*
            |--------------------------------------------------------------------------
            | Get Production Demand
            |--------------------------------------------------------------------------
            */

            $productionDemand =
                $itemPurchasePlanningService
                    ->getProductionDemand(
                        $item,
                        $leadTimeDays
                    );


            /*
            |--------------------------------------------------------------------------
            | Save Production-Driven Values
            |--------------------------------------------------------------------------
            */

            if ($productionDemand['has_plan']) {

                $purchaseSettings->update([

                    'monthly_usage' =>
                        $productionDemand['monthly_usage'],

                    'safety_stock' =>
                        $productionDemand['safety_stock'],

                    'planning_start_date' =>
                        $productionDemand['planning_start_date'],
                ]);
            }


            /*
            |--------------------------------------------------------------------------
            | Sync Opening Stock Movement
            |--------------------------------------------------------------------------
            |
            | Keep only one Opening Stock movement for the Item.
            |
            | Example:
            |
            | Opening Stock 100 -> Current Stock includes 100
            |
            | If changed:
            | 100 -> 150
            |
            | Existing movement becomes 150.
            | It does NOT create another 150 movement.
            |
            */

            $openingStock =
                (float) ($validated['opening_stock'] ?? 0);


            $openingStockMovement =
                StockMovement::where(
                    'item_id',
                    $item->id
                )
                    ->where(
                        'movement_type',
                        'opening_stock'
                    )
                    ->where(
                        'source_type',
                        'item'
                    )
                    ->where(
                        'source_id',
                        $item->id
                    )
                    ->first();


            /*
            |--------------------------------------------------------------------------
            | Opening Stock Greater Than Zero
            |--------------------------------------------------------------------------
            */

            if ($openingStock > 0) {

                /*
                |--------------------------------------------------------------------------
                | Existing Opening Stock Movement
                |--------------------------------------------------------------------------
                */

                if ($openingStockMovement) {

                    $openingStockMovement->update([

                        'direction' =>
                            'in',

                        'quantity' =>
                            $openingStock,

                        'reference' =>
                            'Opening Stock',

                        'remarks' =>
                            'Opening stock synchronized from Item settings.',
                    ]);

                } else {

                    /*
                    |--------------------------------------------------------------------------
                    | Create Opening Stock Movement
                    |--------------------------------------------------------------------------
                    */

                    StockMovement::create([

                        'item_id' =>
                            $item->id,

                        'movement_date' =>
                            now()->toDateString(),

                        'movement_type' =>
                            'opening_stock',

                        'direction' =>
                            'in',

                        'quantity' =>
                            $openingStock,

                        'source_type' =>
                            'item',

                        'source_id' =>
                            $item->id,

                        'reference' =>
                            'Opening Stock',

                        'remarks' =>
                            'Opening stock created from Item settings.',
                    ]);
                }

            } else {

                /*
                |--------------------------------------------------------------------------
                | Opening Stock Changed To Zero
                |--------------------------------------------------------------------------
                */

                if ($openingStockMovement) {

                    $openingStockMovement->delete();
                }
            }

        });


        /*
        |--------------------------------------------------------------------------
        | Redirect
        |--------------------------------------------------------------------------
        */

        return redirect()
            ->route('items.index')
            ->with(
                'success',
                'Item updated successfully.'
            );
    }
    /**
     * Remove the specified item.
     */
    public function destroy(Item $item)
    {
        try {

            $item->delete();

            return redirect()
                ->route('items.index')
                ->with('success', 'Item deleted successfully.');
        } catch (\Illuminate\Database\QueryException $e) {

            return redirect()
                ->route('items.index')
                ->with(
                    'error',
                    'This item cannot be deleted because it is being used by other records.'
                );
        }
    }
    public function searchProductModels(Request $request)
    {
        $search = trim($request->get('q', ''));

        $models = ProductModel::query()
            ->when($search, function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('model_code', 'LIKE', '%' . $search . '%')
                        ->orWhere('document_no', 'LIKE', '%' . $search . '%');
                });
            })
            ->orderBy('model_code')
            ->limit(20)
            ->get();

        $results = $models->map(function ($model) {
            return [
                'id' => $model->id,
                'text' => $model->model_code
                    . ($model->document_no ? ' - ' . $model->document_no : ''),
            ];
        });

        return response()->json([
            'results' => $results,
        ]);
    }
    public function searchItemCodes(Request $request)
    {
        $search = trim($request->get('q', ''));

        $productModelId = $request->get('product_model_id');

        $items = Item::query()
            ->when($productModelId, function ($query) use ($productModelId) {

                $query->whereHas('productModelItems', function ($q) use ($productModelId) {
                    $q->where('product_model_id', $productModelId);
                });
            })
            ->when($search, function ($query) use ($search) {

                $query->where(function ($q) use ($search) {
                    $q->where('item_code', 'LIKE', '%' . $search . '%')
                        ->orWhere('item_name', 'LIKE', '%' . $search . '%');
                });
            })
            ->orderBy('item_code')
            ->limit(20)
            ->get();

        $results = $items->map(function ($item) {
            return [
                'id' => $item->id,
                'text' => $item->item_code . ' - ' . $item->item_name,
            ];
        });

        return response()->json([
            'results' => $results,
        ]);
    }

    // Quick search for items by item code or name, optionally filtered by product model
    /**
     * Quick Edit page: Product Model dropdown + items list.
     */
    public function quickEdit()
    {
        $productModels = ProductModel::where('status', true)
            ->orderBy('model_code')
            ->get();

        return view('items.quick-edit', compact('productModels'));
    }

    /**
     * JSON: items of a Product Model (same filter as items.index).
     */
    public function quickEditList(Request $request)
    {
        $request->validate([
            'product_model_id' => ['required', 'exists:product_models,id'],
        ]);

        $items = Item::with(['defaultSupplier', 'suppliers'])
            ->whereHas('productModelItems', function ($q) use ($request) {
                $q->where('product_model_id', $request->product_model_id);
            })
            ->orderBy('item_code')
            ->get();

        return response()->json([
            'items' => $items->map(fn($item) => $this->quickEditRow($item))->values(),
        ]);
    }

    /**
     * Update only Item Code, Item Name and Unit Price.
     */
    public function quickUpdate(Request $request, Item $item)
    {
        $validated = $request->validate([
            'item_code' => [
                'required',
                'string',
                'max:50',
                Rule::unique('items', 'item_code')
                    ->where(fn($q) => $q->where('product_model_id', $item->product_model_id))
                    ->ignore($item->id),
            ],
            'item_name' => ['required', 'string', 'max:200'],
            'unit_price' => ['nullable', 'numeric', 'min:0'],
        ]);

        // Unit price is stored per supplier in item_suppliers,
        // so we save it against the item's default supplier.
        if (
            $request->filled('unit_price')
            && !$item->default_supplier_id
        ) {
            return response()->json([
                'message' => 'Unit price cannot be saved.',
                'errors' => [
                    'unit_price' => ['This item has no default supplier. Set one in the full Edit Item page first.'],
                ],
            ], 422);
        }

        DB::transaction(function () use ($item, $validated) {

            $item->update([
                'item_code' => $validated['item_code'],
                'item_name' => $validated['item_name'],
            ]);

            if ($item->default_supplier_id) {
                // Updates the pivot row if it exists, creates it if not
                $item->suppliers()->syncWithoutDetaching([
                    $item->default_supplier_id => [
                        'unit_price' => $validated['unit_price'] ?? null,
                    ],
                ]);
            }
        });

        $item->load(['defaultSupplier', 'suppliers']);

        return response()->json([
            'message' => 'Item updated successfully.',
            'item' => $this->quickEditRow($item),
        ]);
    }

    private function quickEditRow(Item $item): array
    {
        $supplierRow = $item->default_supplier_id
            ? $item->suppliers->firstWhere('id', $item->default_supplier_id)
            : null;

        return [
            'id' => $item->id,
            'item_code' => $item->item_code,
            'item_name' => $item->item_name,
            'supplier' => $item->defaultSupplier->name ?? null,
            'has_supplier' => (bool) $item->default_supplier_id,
            'unit_price' => $supplierRow?->pivot->unit_price,
            'update_url' => route('items.quick-update', $item),
        ];
    }
}
