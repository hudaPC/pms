<?php

namespace App\Http\Controllers;

use App\Models\Item;
use App\Models\ProductionPlan;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\Supplier;
use App\Services\ItemPurchasePlanningService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\SupplierInvoice;

class PurchaseOrderController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Purchase Order List
    |--------------------------------------------------------------------------
    */

    public function index()
    {
        $purchaseOrders = PurchaseOrder::with('supplier')
            ->latest('order_date')
            ->latest('id')
            ->paginate(20);

        return view(
            'purchase-orders.index',
            compact('purchaseOrders')
        );
    }

   
    /*
    |--------------------------------------------------------------------------
    | Create Purchase Order
    |--------------------------------------------------------------------------
    */

    public function create(Request $request)
    {
        /*
    |--------------------------------------------------------------------------
    | Active Suppliers
    |--------------------------------------------------------------------------
    |
    | Used for normal/manual Purchase Order creation.
    |
    */

        $suppliers = Supplier::where('status', true)
            ->orderBy('name')
            ->get();


        /*
    |--------------------------------------------------------------------------
    | Planning Source Data
    |--------------------------------------------------------------------------
    */

        $planningItem = null;

        $planningSuppliers = collect();

        $recommendedQty = null;

        $plannedOrderDate = null;

        $selectedSupplierId = null;

        $planningSupplierLeadTimeDays = null;

        $planningSupplierUnitPrice = null;

        $planningSupplierMinimumOrderQty = null;

        $expectedDeliveryDate = null;

        $source =
            $request->query('source');


        /*
    |--------------------------------------------------------------------------
    | Coming From Purchase Planning
    |--------------------------------------------------------------------------
    */

        if (
            $source === 'purchase-planning'
            &&
            $request->filled('item_id')
        ) {

            /*
        |--------------------------------------------------------------------------
        | Load Planning Item
        |--------------------------------------------------------------------------
        */

            $planningItem = Item::with([
                'purchaseSettings',
            ])
                ->find(
                    $request->query('item_id')
                );


            /*
        |--------------------------------------------------------------------------
        | Recommended Quantity
        |--------------------------------------------------------------------------
        */

            if (
                $request->filled(
                    'recommended_qty'
                )
            ) {

                $recommendedQty =
                    (float) $request->query(
                        'recommended_qty'
                    );
            }


            /*
        |--------------------------------------------------------------------------
        | Planned Order Date
        |--------------------------------------------------------------------------
        */

            if (
                $request->filled(
                    'planned_order_date'
                )
            ) {

                $plannedOrderDate =
                    $request->query(
                        'planned_order_date'
                    );
            }


            if ($plannedOrderDate) {

                $plannedDate =
                    Carbon::parse(
                        $plannedOrderDate
                    )->startOfDay();

                $today =
                    now()->startOfDay();


                /*
    |--------------------------------------------------------------------------
    | Future Planned Date
    |--------------------------------------------------------------------------
    */

                if ($plannedDate->gt($today)) {

                    $daysUntilOrder =
                        (int) $today->diffInDays(
                            $plannedDate
                        );


                    return redirect()
                        ->route(
                            'items.purchase-planning.index',
                            $planningItem
                        )
                        ->with(
                            'warning',
                            'This Purchase Order is scheduled for '
                            . $plannedDate->format('d-M-Y')
                            . '. It cannot be created yet. '
                            . $daysUntilOrder
                            . ' day'
                            . ($daysUntilOrder === 1 ? '' : 's')
                            . ' remaining.'
                        );
                }
            }

            /*
        |--------------------------------------------------------------------------
        | Active Suppliers Assigned To Planning Item
        |--------------------------------------------------------------------------
        |
        | IMPORTANT:
        |
        | Do not show every supplier in the Supplier master.
        |
        | Show only suppliers assigned to this item through
        | item_suppliers where:
        |
        | status = active
        |
        | Preferred supplier is ordered first.
        |
        */

            if ($planningItem) {

                $planningSuppliers =
                    $planningItem->suppliers()
                        ->where(
                            'item_suppliers.status',
                            true
                        )
                        ->orderByDesc(
                            'item_suppliers.is_preferred'
                        )
                        ->orderBy(
                            'suppliers.name'
                        )
                        ->get();
            }


            /*
        |--------------------------------------------------------------------------
        | Find Active Preferred Supplier
        |--------------------------------------------------------------------------
        */

            $preferredSupplier =
                $planningSuppliers
                    ->first(
                        function ($supplier) {

                            return (bool) 
                                $supplier
                                    ->pivot
                                    ->is_preferred;
                        }
                    );


            /*
        |--------------------------------------------------------------------------
        | Preferred Supplier Values
        |--------------------------------------------------------------------------
        */

            if ($preferredSupplier) {

                /*
            |--------------------------------------------------------------------------
            | Supplier
            |--------------------------------------------------------------------------
            */

                $selectedSupplierId =
                    $preferredSupplier->id;


                /*
            |--------------------------------------------------------------------------
            | Supplier Lead Time
            |--------------------------------------------------------------------------
            |
            | Priority:
            |
            | 1. Item Supplier lead_time_days
            | 2. Item Purchase Settings lead_time_days
            | 3. System fallback = 60 days
            |
            */

                if (
                    $preferredSupplier
                        ->pivot
                        ->lead_time_days !== null
                ) {

                    $planningSupplierLeadTimeDays =
                        (int) $preferredSupplier
                            ->pivot
                            ->lead_time_days;
                } elseif (
                    $planningItem
                        ->purchaseSettings
                    &&
                    $planningItem
                        ->purchaseSettings
                        ->lead_time_days !== null
                ) {

                    $planningSupplierLeadTimeDays =
                        (int) $planningItem
                            ->purchaseSettings
                            ->lead_time_days;
                } else {

                    $planningSupplierLeadTimeDays =
                        60;
                }


                /*
            |--------------------------------------------------------------------------
            | Supplier Unit Price
            |--------------------------------------------------------------------------
            */

                $planningSupplierUnitPrice =
                    $preferredSupplier
                        ->pivot
                        ->unit_price !== null
                    ? (float) $preferredSupplier
                        ->pivot
                        ->unit_price
                    : null;


                /*
            |--------------------------------------------------------------------------
            | Supplier Minimum Order Quantity
            |--------------------------------------------------------------------------
            */

                $planningSupplierMinimumOrderQty =
                    $preferredSupplier
                        ->pivot
                        ->minimum_order_qty !== null
                    ? (float) $preferredSupplier
                        ->pivot
                        ->minimum_order_qty
                    : null;


                /*
            |--------------------------------------------------------------------------
            | Expected Delivery Date
            |--------------------------------------------------------------------------
            |
            | Planned Order Date
            | +
            | Preferred Supplier Lead Time
            |
            */

                if (
                    $plannedOrderDate
                    &&
                    $planningSupplierLeadTimeDays !== null
                ) {

                    $expectedDeliveryDate =
                        Carbon::parse(
                            $plannedOrderDate
                        )
                            ->addDays(
                                $planningSupplierLeadTimeDays
                            )
                            ->format('Y-m-d');
                }
            }
        }


        /*
    |--------------------------------------------------------------------------
    | Generate Next PO Number
    |--------------------------------------------------------------------------
    |
    | Format:
    |
    | PO-YYYYMMDD-0001
    |
    */

        $datePrefix =
            'PO-'
            . now()->format('Ymd')
            . '-';


        $lastPurchaseOrder =
            PurchaseOrder::where(
                'po_number',
                'like',
                $datePrefix . '%'
            )
                ->orderByDesc(
                    'po_number'
                )
                ->first();


        if ($lastPurchaseOrder) {

            $lastSequence =
                (int) substr(
                    $lastPurchaseOrder
                        ->po_number,
                    -4
                );

            $nextSequence =
                $lastSequence + 1;
        } else {

            $nextSequence = 1;
        }


        $poNumber =
            $datePrefix
            . str_pad(
                $nextSequence,
                4,
                '0',
                STR_PAD_LEFT
            );


        /*
    |--------------------------------------------------------------------------
    | Create View
    |--------------------------------------------------------------------------
    */

        return view(
            'purchase-orders.create',
            compact(
                /*
            |--------------------------------------------------------------------------
            | Normal Supplier List
            |--------------------------------------------------------------------------
            */

                'suppliers',

                /*
            |--------------------------------------------------------------------------
            | PO
            |--------------------------------------------------------------------------
            */

                'poNumber',

                /*
            |--------------------------------------------------------------------------
            | Purchase Planning
            |--------------------------------------------------------------------------
            */

                'planningItem',
                'planningSuppliers',
                'recommendedQty',
                'plannedOrderDate',
                'selectedSupplierId',

                /*
            |--------------------------------------------------------------------------
            | Preferred Supplier Values
            |--------------------------------------------------------------------------
            */

                'planningSupplierLeadTimeDays',
                'planningSupplierUnitPrice',
                'planningSupplierMinimumOrderQty',
                'expectedDeliveryDate',

                /*
            |--------------------------------------------------------------------------
            | Source
            |--------------------------------------------------------------------------
            */

                'source'
            )
        );
    }

   public function store(Request $request)
{
    /*
    |--------------------------------------------------------------------------
    | Validation
    |--------------------------------------------------------------------------
    */

    $validated = $request->validate([

        'po_number' => [
            'required',
            'string',
            'max:50',
            'unique:purchase_orders,po_number',
        ],

        'supplier_id' => [
            'required',
            'exists:suppliers,id',
        ],

        'order_date' => [
            'required',
            'date',
        ],

        'expected_delivery_date' => [
            'nullable',
            'date',
            'after_or_equal:order_date',
        ],

        'remarks' => [
            'nullable',
            'string',
        ],


        /*
        |--------------------------------------------------------------------------
        | Purchase Planning Data
        |--------------------------------------------------------------------------
        */

        'planning_item_id' => [
            'nullable',
            'exists:items,id',
        ],

        'planning_recommended_qty' => [
            'nullable',
            'numeric',
            'min:0.001',
        ],

        'planning_planned_order_date' => [
            'nullable',
            'date',
        ],

        'planning_source' => [
            'nullable',
            'string',
            'in:purchase-planning',
        ],

    ]);


    /*
    |--------------------------------------------------------------------------
    | Purchase Planning Source
    |--------------------------------------------------------------------------
    */

    $planningSource =
        $validated['planning_source']
        ?? null;


    /*
    |--------------------------------------------------------------------------
    | Purchase Planning Values
    |--------------------------------------------------------------------------
    */

    $planningItemId =
        $validated['planning_item_id']
        ?? null;


    $planningRecommendedQty =
        isset($validated['planning_recommended_qty'])
            ? (float) $validated['planning_recommended_qty']
            : null;


    $planningPlannedOrderDate =
        $validated['planning_planned_order_date']
        ?? null;


    /*
    |--------------------------------------------------------------------------
    | Validate Purchase Planning Data
    |--------------------------------------------------------------------------
    */

    if (
        $planningSource === 'purchase-planning'
        &&
        !$planningItemId
    ) {

        throw \Illuminate\Validation\ValidationException::withMessages([

            'planning_item_id' =>
                'Purchase Planning item information is missing.',

        ]);
    }


    /*
    |--------------------------------------------------------------------------
    | Prevent Early Purchase Order Submission
    |--------------------------------------------------------------------------
    |
    | Purchase Planning POs cannot be created before their planned
    | order date.
    |
    */

    if (
        $planningSource === 'purchase-planning'
        &&
        $planningItemId
        &&
        $planningPlannedOrderDate
    ) {

        $plannedDate =
            \Carbon\Carbon::parse(
                $planningPlannedOrderDate
            )->startOfDay();


        $today =
            now()->startOfDay();


        if ($plannedDate->gt($today)) {

            $daysUntilOrder =
                (int) $today->diffInDays(
                    $plannedDate
                );


            return redirect()
                ->route(
                    'items.purchase-planning.index',
                    $planningItemId
                )
                ->with(
                    'warning',
                    'This Purchase Order is scheduled for '
                    . $plannedDate->format('d-M-Y')
                    . '. It cannot be created yet. '
                    . $daysUntilOrder
                    . ' day'
                    . ($daysUntilOrder === 1 ? '' : 's')
                    . ' remaining.'
                );
        }
    }


    /*
    |--------------------------------------------------------------------------
    | Set PO Source
    |--------------------------------------------------------------------------
    */

    if (
        $planningSource === 'purchase-planning'
    ) {

        $validated['source'] =
            'purchase-planning';

        $validated['production_plan_id'] =
            null;

    } else {

        /*
        |--------------------------------------------------------------------------
        | Manual Purchase Order
        |--------------------------------------------------------------------------
        */

        $validated['source'] =
            'manual';

        $validated['production_plan_id'] =
            null;
    }


    /*
    |--------------------------------------------------------------------------
    | Remove Request-Only Planning Fields
    |--------------------------------------------------------------------------
    */

    unset(
        $validated['planning_item_id'],
        $validated['planning_recommended_qty'],
        $validated['planning_planned_order_date'],
        $validated['planning_source']
    );


    /*
    |--------------------------------------------------------------------------
    | Initial PO Status
    |--------------------------------------------------------------------------
    */

    $validated['status'] =
        'draft';


    /*
    |--------------------------------------------------------------------------
    | Database Transaction
    |--------------------------------------------------------------------------
    |
    | Manual PO:
    |     Creates PO header only.
    |
    | Purchase Planning PO:
    |     Creates PO header + recommended planning item.
    |
    */

    $purchaseOrder =
        DB::transaction(
            function () use (
                $validated,
                $planningSource,
                $planningItemId,
                $planningRecommendedQty,
                $planningPlannedOrderDate
            ) {

                /*
                |--------------------------------------------------------------------------
                | Create PO Header
                |--------------------------------------------------------------------------
                */

                $purchaseOrder =
                    PurchaseOrder::create(
                        $validated
                    );


                /*
                |--------------------------------------------------------------------------
                | Normal Manual PO
                |--------------------------------------------------------------------------
                */

                if (!$planningSource) {

                    return $purchaseOrder;
                }


                /*
                |--------------------------------------------------------------------------
                | Purchase Planning Mode
                |--------------------------------------------------------------------------
                */

                if (
                    $planningSource ===
                    'purchase-planning'
                ) {

                    /*
                    |--------------------------------------------------------------------------
                    | Validate Recommended Quantity
                    |--------------------------------------------------------------------------
                    */

                    if (
                        !$planningRecommendedQty
                        ||
                        $planningRecommendedQty <= 0
                    ) {

                        throw \Illuminate\Validation\ValidationException::withMessages([

                            'planning_recommended_qty' =>
                                'Purchase Planning did not provide a valid recommended quantity.',

                        ]);
                    }


                    /*
                    |--------------------------------------------------------------------------
                    | Find Planned Item
                    |--------------------------------------------------------------------------
                    |
                    | The item must:
                    |
                    | - exist
                    | - be active
                    | - be available from the selected supplier
                    | - have an active supplier-item relationship
                    |
                    */

                    $item =
                        Item::where(
                            'id',
                            $planningItemId
                        )
                            ->where(
                                'status',
                                true
                            )
                            ->whereHas(
                                'suppliers',
                                function ($query) use ($purchaseOrder) {

                                    $query
                                        ->where(
                                            'suppliers.id',
                                            $purchaseOrder->supplier_id
                                        )
                                        ->where(
                                            'item_suppliers.status',
                                            true
                                        );
                                }
                            )
                            ->first();


                    if (!$item) {

                        throw \Illuminate\Validation\ValidationException::withMessages([

                            'supplier_id' =>
                                'The planned item is not available from the selected supplier.',

                        ]);
                    }


                    /*
                    |--------------------------------------------------------------------------
                    | Supplier Specific Item Information
                    |--------------------------------------------------------------------------
                    */

                    $supplier =
                        $item->suppliers()
                            ->where(
                                'suppliers.id',
                                $purchaseOrder->supplier_id
                            )
                            ->where(
                                'item_suppliers.status',
                                true
                            )
                            ->first();


                    if (!$supplier) {

                        throw \Illuminate\Validation\ValidationException::withMessages([

                            'supplier_id' =>
                                'No active supplier-item configuration was found for this planned item.',

                        ]);
                    }


                    /*
                    |--------------------------------------------------------------------------
                    | Supplier Unit Price
                    |--------------------------------------------------------------------------
                    */

                    $unitPrice =
                        $supplier
                            ->pivot
                            ->unit_price
                        !== null
                            ? (float) $supplier
                                ->pivot
                                ->unit_price
                            : null;


                    /*
                    |--------------------------------------------------------------------------
                    | Create Purchase Planning PO Item
                    |--------------------------------------------------------------------------
                    */

                    PurchaseOrderItem::create([

                        'purchase_order_id' =>
                            $purchaseOrder->id,

                        'item_id' =>
                            $item->id,

                        'ordered_quantity' =>
                            $planningRecommendedQty,

                        'unit_price' =>
                            $unitPrice,

                        'received_quantity' =>
                            0,

                        'planned_order_date' =>
                            $planningPlannedOrderDate,

                        'remarks' =>
                            'Created from Purchase Planning recommendation.',

                    ]);


                    return $purchaseOrder;
                }


                /*
                |--------------------------------------------------------------------------
                | Invalid Source Safety
                |--------------------------------------------------------------------------
                */

                throw \Illuminate\Validation\ValidationException::withMessages([

                    'planning_source' =>
                        'Invalid Purchase Order source.',

                ]);
            }
        );


    /*
    |--------------------------------------------------------------------------
    | Purchase Planning Redirect
    |--------------------------------------------------------------------------
    */

    if (
        $planningSource === 'purchase-planning'
        &&
        $planningItemId
    ) {

        return redirect()
            ->route(
                'purchase-orders.show',
                $purchaseOrder
            )
            ->with(
                'success',
                'Purchase order created from Purchase Planning. '
                . 'The recommended item and quantity were added automatically.'
            );
    }


    /*
    |--------------------------------------------------------------------------
    | Normal Manual PO Redirect
    |--------------------------------------------------------------------------
    */

    return redirect()
        ->route(
            'purchase-orders.show',
            $purchaseOrder
        )
        ->with(
            'success',
            'Purchase order created successfully. You can now add items.'
        );
}


    public function show(PurchaseOrder $purchaseOrder)
    {
        $purchaseOrder->load([
            'supplier',
            'items.item.unit',
            'goodsReceipts.items',
        ]);

        return view(
            'purchase-orders.show',
            compact('purchaseOrder')
        );
    }
    /*
|--------------------------------------------------------------------------
| Edit Purchase Order
|--------------------------------------------------------------------------
*/

    public function edit(PurchaseOrder $purchaseOrder)
    {
        /*
        |--------------------------------------------------------------------------
        | Only Draft PO Can Be Edited
        |--------------------------------------------------------------------------
        */

        abort_if(
            $purchaseOrder->status !== 'draft',
            403,
            'Only draft purchase orders can be edited.'
        );

        /*
        |--------------------------------------------------------------------------
        | Supplier Invoice Purchase Order Header Is Locked
        |--------------------------------------------------------------------------
        */

        abort_if(
            $purchaseOrder->source === 'supplier-invoice',
            403,
            'This Purchase Order was created from a Supplier Invoice and cannot be edited manually.'
        );
        /*
        |--------------------------------------------------------------------------
        | Active Suppliers
        |--------------------------------------------------------------------------
        */

        $suppliers = Supplier::where('status', true)
            ->orderBy('name')
            ->get();


        /*
        |--------------------------------------------------------------------------
        | Edit View
        |--------------------------------------------------------------------------
        */

        return view(
            'purchase-orders.edit',
            compact(
                'purchaseOrder',
                'suppliers'
            )
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Update Purchase Order
    |--------------------------------------------------------------------------
    */

    public function update(
        Request $request,
        PurchaseOrder $purchaseOrder
    ) {
        /*
        |--------------------------------------------------------------------------
        | Only Draft PO Can Be Updated
        |--------------------------------------------------------------------------
        */

        abort_if(
            $purchaseOrder->status !== 'draft',
            403,
            'Only draft purchase orders can be edited.'
        );
        /*
        |--------------------------------------------------------------------------
        | Supplier Invoice Purchase Order Header Cannot Be Updated
        |--------------------------------------------------------------------------
        */

        abort_if(
            $purchaseOrder->source === 'supplier-invoice',
            403,
            'This Purchase Order was created from a Supplier Invoice and cannot be updated manually.'
        );


        /*
        |--------------------------------------------------------------------------
        | Validation
        |--------------------------------------------------------------------------
        */

        $validated = $request->validate([

            'supplier_id' => [
                'required',
                'exists:suppliers,id',
            ],

            'order_date' => [
                'required',
                'date',
            ],

            'expected_delivery_date' => [
                'nullable',
                'date',
                'after_or_equal:order_date',
            ],

            'remarks' => [
                'nullable',
                'string',
            ],

        ]);


        /*
        |--------------------------------------------------------------------------
        | Update Purchase Order
        |--------------------------------------------------------------------------
        |
        | PO Number is NOT changed during edit.
        |
        */

        $purchaseOrder->update([

            'supplier_id' =>
                $validated['supplier_id'],

            'order_date' =>
                $validated['order_date'],

            'expected_delivery_date' =>
                $validated['expected_delivery_date'] ?? null,

            'remarks' =>
                $validated['remarks'] ?? null,

        ]);


        /*
        |--------------------------------------------------------------------------
        | Redirect
        |--------------------------------------------------------------------------
        */

        return redirect()
            ->route(
                'purchase-orders.show',
                $purchaseOrder
            )
            ->with(
                'success',
                'Purchase order updated successfully.'
            );
    }
    public function confirm(PurchaseOrder $purchaseOrder)
    {
        /*
    |--------------------------------------------------------------------------
    | Only Draft PO Can Be Confirmed
    |--------------------------------------------------------------------------
    */

        abort_if(
            $purchaseOrder->status !== 'draft',
            403,
            'Only draft purchase orders can be confirmed.'
        );


        /*
    |--------------------------------------------------------------------------
    | Load PO Items
    |--------------------------------------------------------------------------
    */

        $purchaseOrder->load('items');


        /*
    |--------------------------------------------------------------------------
    | Must Have At Least One Item
    |--------------------------------------------------------------------------
    */

        if ($purchaseOrder->items->isEmpty()) {

            return back()->withErrors([
                'confirm' =>
                    'The purchase order cannot be confirmed because it has no items.',
            ]);
        }


        /*
    |--------------------------------------------------------------------------
    | Expected Delivery Date Is Required
    |--------------------------------------------------------------------------
    */

        if (!$purchaseOrder->expected_delivery_date) {

            return back()->withErrors([
                'confirm' =>
                    'Expected Delivery Date is required before confirming the purchase order.',
            ]);
        }


        /*
    |--------------------------------------------------------------------------
    | Expected Delivery Cannot Be Before Order Date
    |--------------------------------------------------------------------------
    */

        if (
            $purchaseOrder->expected_delivery_date
                ->lt($purchaseOrder->order_date)
        ) {

            return back()->withErrors([
                'confirm' =>
                    'Expected Delivery Date cannot be earlier than the Order Date.',
            ]);
        }


        /*
    |--------------------------------------------------------------------------
    | Validate PO Item Quantities
    |--------------------------------------------------------------------------
    */

        foreach ($purchaseOrder->items as $poItem) {

            if ((float) $poItem->ordered_quantity <= 0) {

                return back()->withErrors([
                    'confirm' =>
                        'All purchase order items must have a valid ordered quantity.',
                ]);
            }
        }


        /*
    |--------------------------------------------------------------------------
    | Confirm Purchase Order
    |--------------------------------------------------------------------------
    */

        $purchaseOrder->update([
            'status' => 'confirmed',
        ]);


        /*
    |--------------------------------------------------------------------------
    | Redirect
    |--------------------------------------------------------------------------
    */

        return redirect()
            ->route(
                'purchase-orders.show',
                $purchaseOrder
            )
            ->with(
                'success',
                'Purchase order confirmed successfully.'
            );
    }

    /*
|--------------------------------------------------------------------------
| Cancel Purchase Order
|--------------------------------------------------------------------------
|
| Only Draft or Confirmed Purchase Orders can be cancelled.
|
| Partially Received / Received Purchase Orders cannot be cancelled
| because receiving activity already exists.
|
*/

    public function cancel(PurchaseOrder $purchaseOrder)
    {
        /*
    |--------------------------------------------------------------------------
    | Already Cancelled
    |--------------------------------------------------------------------------
    */

        if ($purchaseOrder->status === 'cancelled') {

            return back()->withErrors([
                'cancel' =>
                    'This Purchase Order has already been cancelled.',
            ]);
        }


        /*
    |--------------------------------------------------------------------------
    | Block Partially Received / Received
    |--------------------------------------------------------------------------
    */

        if (
            in_array(
                $purchaseOrder->status,
                [
                    'partially_received',
                    'received',
                ],
                true
            )
        ) {

            return back()->withErrors([
                'cancel' =>
                    'This Purchase Order cannot be cancelled because goods have already been received against it.',
            ]);
        }


        /*
    |--------------------------------------------------------------------------
    | Only Draft / Confirmed Can Be Cancelled
    |--------------------------------------------------------------------------
    */

        if (
            !in_array(
                $purchaseOrder->status,
                [
                    'draft',
                    'confirmed',
                ],
                true
            )
        ) {

            return back()->withErrors([
                'cancel' =>
                    'This Purchase Order cannot be cancelled in its current status.',
            ]);
        }


        /*
        |--------------------------------------------------------------------------
        | Cancel Purchase Order
        |--------------------------------------------------------------------------
        */

        DB::transaction(function () use ($purchaseOrder) {
            /*
|--------------------------------------------------------------------------
| Find Linked Supplier Invoice
|--------------------------------------------------------------------------
*/

$linkedSupplierInvoice = null;

if ($purchaseOrder->source === 'supplier-invoice') {

    $linkedSupplierInvoice =
        SupplierInvoice::where(
            'purchase_order_id',
            $purchaseOrder->id
        )
        ->lockForUpdate()
        ->first();
}
 /*
    |--------------------------------------------------------------------------
    | Unlink Supplier Invoice Items
    |--------------------------------------------------------------------------
    */

    if ($linkedSupplierInvoice) {

        $linkedSupplierInvoice
            ->items()
            ->update([
                'purchase_order_item_id' => null,
            ]);
    }
    /*
|--------------------------------------------------------------------------
| Unlink Supplier Invoice From Cancelled Purchase Order
|--------------------------------------------------------------------------
*/

if ($linkedSupplierInvoice) {

    $linkedSupplierInvoice->update([
        'purchase_order_id' => null,
    ]);
}

  /*
    |--------------------------------------------------------------------------
    | Cancel Purchase Order
    |--------------------------------------------------------------------------
    */

            $purchaseOrder->update([
                'status' => 'cancelled',
            ]);

        });


        /*
    |--------------------------------------------------------------------------
    | Redirect
    |--------------------------------------------------------------------------
    */

        return redirect()
            ->route(
                'purchase-orders.show',
                $purchaseOrder
            )
            ->with(
                'success',
                'Purchase Order cancelled successfully.'
            );
    }
}
