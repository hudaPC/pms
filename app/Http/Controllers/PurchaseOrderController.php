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
use Barryvdh\DomPDF\Facade\Pdf;

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
        | PO Number
        |--------------------------------------------------------------------------
        |
        | PO Number is entered manually by the user.
        |
        */

        $poNumber = null;


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
        | Validate Manual Purchase Order
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
            'source' => [
                'nullable',
                'string',
            ],

            'planning_item_id' => [
                'nullable',
                'integer',
                'exists:items,id',
            ],

            'recommended_qty' => [
                'nullable',
                'numeric',
                'min:0',
            ],

            'planned_order_date' => [
                'nullable',
                'date',
            ],

        ]);


        /*
|--------------------------------------------------------------------------
| Purchase Order Source
|--------------------------------------------------------------------------
*/

        $isPurchasePlanning =
            $request->input('source') === 'purchase-planning'
            &&
            $request->filled('planning_item_id')
            &&
            $request->filled('recommended_qty');


        $validated['source'] =
            $isPurchasePlanning
            ? 'purchase-planning'
            : 'manual';

        $validated['production_plan_id'] = null;

        $validated['status'] = 'draft';

        /*
|--------------------------------------------------------------------------
| Planning Context
|--------------------------------------------------------------------------
|
| Keep these values for the redirect only.
| They are not columns in purchase_orders.
|
*/

        $planningItemId =
            $validated['planning_item_id']
            ?? null;

        $recommendedQty =
            $validated['recommended_qty']
            ?? null;

        $plannedOrderDate =
            $validated['planned_order_date']
            ?? null;
        /*
|--------------------------------------------------------------------------
| Purchase Planning Date Protection
|--------------------------------------------------------------------------
|
| A future planned order must not create a PO header.
| Manual Purchase Orders are unaffected.
|
*/

        if ($isPurchasePlanning) {

            if (!$plannedOrderDate) {

                return back()
                    ->withInput()
                    ->withErrors([
                        'planned_order_date' =>
                            'The planned order date is required for Purchase Planning.',
                    ]);
            }

            $plannedDate = Carbon::parse(
                $plannedOrderDate
            )->startOfDay();

            if ($plannedDate->gt(now()->startOfDay())) {

                return back()
                    ->withInput()
                    ->withErrors([
                        'planned_order_date' =>
                            'This Purchase Order is scheduled for '
                            . $plannedDate->format('d-M-Y')
                            . ' and cannot be created yet.',
                    ]);
            }
        }

        unset(
            $validated['planning_item_id'],
            $validated['recommended_qty'],
            $validated['planned_order_date']
        );

        /*
        |--------------------------------------------------------------------------
        | Create Purchase Order
        |--------------------------------------------------------------------------
        */

        $purchaseOrder = PurchaseOrder::create(
            $validated
        );


        /*
        |--------------------------------------------------------------------------
        | Redirect
        |--------------------------------------------------------------------------
        |
        | Purchase Planning:
        | Go directly to Add Item and preserve the recommended item/quantity.
        |
        | Normal Manual PO:
        | Keep the existing PO Show workflow.
        |
        */

        if ($isPurchasePlanning) {

            return redirect()
                ->route(
                    'purchase-orders.items.create',
                    [
                        'purchaseOrder' => $purchaseOrder,
                        'planning_item_id' => $planningItemId,
                        'recommended_qty' => $recommendedQty,
                        'planned_order_date' => $plannedOrderDate,
                        'source' => 'purchase-planning',
                    ]
                )
                ->with(
                    'success',
                    'Purchase Order header created. Review and add the recommended item.'
                );
        }


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
        'items.productModel',
                'goodsReceipts.items',
    ]);

    return view(
        'purchase-orders.show',
        compact('purchaseOrder')
    );
}
    public function pdf(PurchaseOrder $purchaseOrder)
    {
        $purchaseOrder->setRelation(
    'items',
    $purchaseOrder->items->sortBy(function ($line) {
        $parts = explode('-', $line->item->item_code ?? '');

        // Sort by the G-number (e.g. G001) first, then by the model part (e.g. M0029L)
        return ($parts[1] ?? '') . '|' . ($parts[0] ?? '');
    }, SORT_NATURAL)->values()
);
        $totalQty = $purchaseOrder->items->sum('ordered_quantity');

        $totalPrice = $purchaseOrder->items->sum(function ($item) {
            return (float) $item->ordered_quantity * (float) ($item->unit_price ?? 0);
        });

       $totalPriceInWords = $this->amountToWords($totalPrice);

        // Convert the heading image to base64 so DomPDF can embed it reliably
        $headingPath = public_path('images/heading.jpg');

        $headingImageData = file_exists($headingPath)
            ? 'data:image/jpeg;base64,' . base64_encode(file_get_contents($headingPath))
            : null;

        $pdf = Pdf::setOptions(['isPhpEnabled' => true]);

        $pdf = Pdf::loadView('purchase-orders.pdf', compact(
            'purchaseOrder',
            'totalQty',
            'totalPrice',
            'totalPriceInWords',
            'headingImageData'
        ))->setPaper('a4', 'portrait');
        $pdf->setOption('isPhpEnabled', true);

        $safeFileName = str_replace(['/', '\\'], '-', $purchaseOrder->po_number);

        return $pdf->download('PO-' . $safeFileName . '.pdf');
    }


    private function amountToWords(float $amount): string
    {
        $amount = round($amount, 2);

        $whole = (int) floor($amount);
        $cents = (int) round(($amount - $whole) * 100);

        if ($cents === 100) {
            $whole++;
            $cents = 0;
        }

        $wholeWords = $whole > 0 ? $this->numberToWords($whole) : '';
        $centsWords = $cents > 0 ? $this->threeDigitsToWords($cents) . ' Cents' : '';

        if ($wholeWords !== '' && $centsWords !== '') {
            return $wholeWords . ' and ' . $centsWords . ' Only';
        }

        if ($wholeWords !== '') {
            return $wholeWords . ' Only';
        }

        if ($centsWords !== '') {
            return $centsWords . ' Only';
        }

        return 'Zero Only';
    }

    private function numberToWords(int $number): string
    {
        if ($number === 0) {
            return '';
        }

        $crore = intdiv($number, 10000000);
        $number %= 10000000;

        $lakh = intdiv($number, 100000);
        $number %= 100000;

        $thousand = intdiv($number, 1000);
        $number %= 1000;

        $hundred = $number;

        $words = '';

        if ($crore > 0) {
            $words .= $this->threeDigitsToWords($crore) . ' Crore ';
        }

        if ($lakh > 0) {
            $words .= $this->threeDigitsToWords($lakh) . ' Lakh ';
        }

        if ($thousand > 0) {
            $words .= $this->threeDigitsToWords($thousand) . ' Thousand ';
        }

        if ($hundred > 0) {
            $words .= $this->threeDigitsToWords($hundred);
        }

        return trim($words);
    }

    private function threeDigitsToWords(int $number): string
    {
        $ones = [
            '',
            'One',
            'Two',
            'Three',
            'Four',
            'Five',
            'Six',
            'Seven',
            'Eight',
            'Nine',
            'Ten',
            'Eleven',
            'Twelve',
            'Thirteen',
            'Fourteen',
            'Fifteen',
            'Sixteen',
            'Seventeen',
            'Eighteen',
            'Nineteen',
        ];

        $tens = ['', '', 'Twenty', 'Thirty', 'Forty', 'Fifty', 'Sixty', 'Seventy', 'Eighty', 'Ninety'];

        $str = '';

        if ($number >= 100) {
            $str .= $ones[intdiv($number, 100)] . ' Hundred ';
            $number %= 100;
        }

        if ($number > 0) {
            $str .= $this->twoDigitsToWords($number, $ones, $tens);
        }

        return trim($str);
    }

    private function twoDigitsToWords(int $number, array $ones, array $tens): string
    {
        if ($number < 20) {
            return $ones[$number];
        }

        return trim($tens[intdiv($number, 10)] . ' ' . $ones[$number % 10]);
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

                $purchaseOrder->update([
            'status' => 'cancelled',
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
                'Purchase Order cancelled successfully.'
            );
    }
}
