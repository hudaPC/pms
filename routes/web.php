<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\UnitController;
use App\Http\Controllers\ItemController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\ItemSupplierController;
use App\Http\Controllers\ItemPurchaseSettingController;
use App\Http\Controllers\PurchasePlanningController;
use App\Http\Controllers\UsageLogController;
use App\Http\Controllers\PurchaseCalendarController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProductModelController;
use App\Http\Controllers\ProductModelItemController;
use App\Http\Controllers\ProductionPlanController;
use App\Http\Controllers\PurchaseOrderController;
use App\Http\Controllers\PurchaseOrderItemController;
use App\Http\Controllers\GoodsReceiptController;
use App\Http\Controllers\GoodsReceiptItemController;
use App\Http\Controllers\InventoryController;
use App\Http\Controllers\StockAdjustmentController;
use App\Http\Controllers\SupplierInvoiceController;
use App\Http\Controllers\SupplierInvoiceItemController;
use App\Http\Controllers\SupplierPaymentController;
use App\Http\Controllers\SupplierLedgerController;
use App\Http\Controllers\AccountsPayableController;
use App\Http\Controllers\PurchaseReturnController;
use App\Http\Controllers\ReportingController;

/*
|--------------------------------------------------------------------------
| Public Routes
|--------------------------------------------------------------------------
*/

// Home
Route::get('/', function () {
    return redirect()->route('login');
});


/*
|--------------------------------------------------------------------------
| Authentication Routes
|--------------------------------------------------------------------------
*/

// Login
Route::get('/login', [AuthController::class, 'showLogin'])
    ->name('login');

Route::post('/login', [AuthController::class, 'login'])
    ->name('login.process');


// Register
Route::get('/register', [AuthController::class, 'showRegister'])
    ->name('register');

Route::post('/register', [AuthController::class, 'register'])
    ->name('register.process');


// Logout
Route::post('/logout', [AuthController::class, 'logout'])
    ->name('logout');


/*
|--------------------------------------------------------------------------
| Protected Application Routes (any logged-in user: admin or user)
|--------------------------------------------------------------------------
*/

Route::middleware(['auth'])->group(function () {

    /*
    |--------------------------------------------------------------------------
    | Dashboard
    |--------------------------------------------------------------------------
    */

    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');


    /*
    |--------------------------------------------------------------------------
    | Material Usage (available to admin AND user)
    |--------------------------------------------------------------------------
    */

    // Usage Logs
    Route::resource(
        'items.usage-logs',
        UsageLogController::class
    )->except(['show']);

    Route::get(
        '/items/{item}/usage-logs/next-reference',
        [UsageLogController::class, 'nextReference']
    )->name('items.usage-logs.next-reference');

    Route::get(
        '/material-usage',
        [UsageLogController::class, 'allUsage']
    )->name('material-usage.index');

    Route::get(
        '/material-usage/create',
        [UsageLogController::class, 'createMaterialUsage']
    )->name('material-usage.create');

    Route::post(
        '/material-usage',
        [UsageLogController::class, 'storeMaterialUsage']
    )->name('material-usage.store');

    Route::get(
        '/material-usage/product-models/{productModel}/items',
        [UsageLogController::class, 'materialUsageItems']
    )->name('material-usage.product-model-items');

    Route::get(
        'material-usage/items/{item}/goods-receipts',
        [UsageLogController::class, 'materialUsageGoodsReceipts']
    )->name('material-usage.item-goods-receipts');





    /*
    |--------------------------------------------------------------------------
    | Item Master (available to admin AND user)
    |--------------------------------------------------------------------------
    */

    // Select2 AJAX
    Route::get('/items-search/product-models', [ItemController::class, 'searchProductModels'])
        ->name('items.search.product-models');

    Route::get('/items-search/item-codes', [ItemController::class, 'searchItemCodes'])
        ->name('items.search.item-codes');

    // Items - view only (everyone can see the list)
    Route::get('/items', [ItemController::class, 'index'])
        ->name('items.index');
    /*
|--------------------------------------------------------------------------
| Categories (available to admin AND user - view only)
|--------------------------------------------------------------------------
*/

    Route::get('/categories', [CategoryController::class, 'index'])
        ->name('categories.index');
    /*
|--------------------------------------------------------------------------
| Units (available to admin AND user - view only)
|--------------------------------------------------------------------------
*/

    Route::get('/units', [UnitController::class, 'index'])
        ->name('units.index');

    /*
|--------------------------------------------------------------------------
| Suppliers (available to admin AND user - view only)
|--------------------------------------------------------------------------
*/

    Route::get('/suppliers', [SupplierController::class, 'index'])
        ->name('suppliers.index');
    /*
|--------------------------------------------------------------------------
| Product Model (available to admin AND user - view only)
|--------------------------------------------------------------------------
*/

    Route::get('/product-models', [ProductModelController::class, 'index'])
        ->name('product-models.index');

    Route::get(
        '/product-models/{productModel}/items',
        [ProductModelItemController::class, 'index']
    )->name('product-models.items.index');



    //INVOICES START
    // Supplier Invoices
    Route::post(
        '/supplier-invoices/generate-number',
        [SupplierInvoiceController::class, 'generateNumber']
    )->name('supplier-invoices.generate-number');

    // create purchase order
    Route::post(
        '/supplier-invoices/{supplierInvoice}/create-purchase-order',
        [
            SupplierInvoiceController::class,
            'createPurchaseOrder'
        ]
    )->name(
            'supplier-invoices.create-purchase-order'
        );

    Route::resource(
        'supplier-invoices',
        SupplierInvoiceController::class
    );

    Route::post(
        '/supplier-invoices/{supplierInvoice}/confirm',
        [SupplierInvoiceController::class, 'confirm']
    )->name('supplier-invoices.confirm');


    Route::post(
        '/supplier-invoices/{supplierInvoice}/cancel',
        [SupplierInvoiceController::class, 'cancel']
    )->name('supplier-invoices.cancel');



    Route::patch(
        '/supplier-invoices/{supplierInvoice}/due-date',
        [
            SupplierInvoiceController::class,
            'updateDueDate'
        ]
    )->name('supplier-invoices.due-date.update');



    // Supplier Invoice Items
    Route::get(
        '/supplier-invoices/{supplierInvoice}/items/create',
        [SupplierInvoiceItemController::class, 'create']
    )->name('supplier-invoices.items.create');

    Route::get(
        '/supplier-invoices/{supplierInvoice}/product-models/{productModel}/items',
        [SupplierInvoiceItemController::class, 'itemsByProductModel']
    )->name(
            'supplier-invoices.product-model-items'
        );
    Route::post(
        '/supplier-invoices/{supplierInvoice}/items',
        [SupplierInvoiceItemController::class, 'store']
    )->name('supplier-invoices.items.store');


    Route::get(
        '/supplier-invoices/{supplierInvoice}/items/{supplierInvoiceItem}/edit',
        [SupplierInvoiceItemController::class, 'edit']
    )->name('supplier-invoices.items.edit');


    Route::put(
        '/supplier-invoices/{supplierInvoice}/items/{supplierInvoiceItem}',
        [SupplierInvoiceItemController::class, 'update']
    )->name('supplier-invoices.items.update');


    Route::delete(
        '/supplier-invoices/{supplierInvoice}/items/{supplierInvoiceItem}',
        [SupplierInvoiceItemController::class, 'destroy']
    )->name('supplier-invoices.items.destroy');


    // INVOICE UPLOAD
    Route::get(
        'supplier-invoices/{supplierInvoice}/items/create-upload',
        [SupplierInvoiceItemController::class, 'createWithUpload']
    )
        ->name('supplier-invoices.items.create-upload');

    Route::post(
        'supplier-invoices/{supplierInvoice}/items/bulk-store',
        [SupplierInvoiceItemController::class, 'storeBulk']
    )
        ->name('supplier-invoices.items.bulk-store');





    //INVENTORY ROUTES
    Route::get(
        '/inventory',
        [InventoryController::class, 'index']
    )->name('inventory.index');


    Route::get(
        '/inventory/{item}/ledger',
        [InventoryController::class, 'ledger']
    )->name('inventory.ledger');

    //STOCK ADJUSTMENT ROUTES
    Route::get(
        '/stock-adjustments/product-models/{productModel}/items',
        [StockAdjustmentController::class, 'itemsByProductModel']
    )->name(
            'stock-adjustments.product-model-items'
        );

    Route::get(
        '/stock-adjustments',
        [StockAdjustmentController::class, 'index']
    )->name('stock-adjustments.index');


    Route::get(
        '/stock-adjustments/create',
        [StockAdjustmentController::class, 'create']
    )->name('stock-adjustments.create');


    Route::post(
        '/stock-adjustments',
        [StockAdjustmentController::class, 'store']
    )->name('stock-adjustments.store');
    Route::get(
        '/stock-adjustments/{stockAdjustment}/edit',
        [StockAdjustmentController::class, 'edit']
    )->name('stock-adjustments.edit');


    Route::put(
        '/stock-adjustments/{stockAdjustment}',
        [StockAdjustmentController::class, 'update']
    )->name('stock-adjustments.update');


    Route::delete(
        '/stock-adjustments/{stockAdjustment}',
        [StockAdjustmentController::class, 'destroy']
    )->name('stock-adjustments.destroy');



    // Search filter
    Route::get(
        '/inventory/item-search',
        [InventoryController::class, 'itemSearch']
    )->name('inventory.item-search');
});


/*
|--------------------------------------------------------------------------
| Admin-Only Application Routes
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'admin'])->group(function () {

    /*
    |--------------------------------------------------------------------------
    | Master Data
    |--------------------------------------------------------------------------
    */


    // Categories - create/edit/delete (admin only)
    Route::get('/categories/create', [CategoryController::class, 'create'])
        ->name('categories.create');

    Route::post('/categories', [CategoryController::class, 'store'])
        ->name('categories.store');

    Route::get('/categories/{category}/edit', [CategoryController::class, 'edit'])
        ->name('categories.edit');

    Route::put('/categories/{category}', [CategoryController::class, 'update'])
        ->name('categories.update');

    Route::delete('/categories/{category}', [CategoryController::class, 'destroy'])
        ->name('categories.destroy');

    // Units - create/edit/delete (admin only)
    Route::get('/units/create', [UnitController::class, 'create'])
        ->name('units.create');

    Route::post('/units', [UnitController::class, 'store'])
        ->name('units.store');

    Route::get('/units/{unit}/edit', [UnitController::class, 'edit'])
        ->name('units.edit');

    Route::put('/units/{unit}', [UnitController::class, 'update'])
        ->name('units.update');

    Route::delete('/units/{unit}', [UnitController::class, 'destroy'])
        ->name('units.destroy');



    // Suppliers - create/edit/delete (admin only)
    Route::get('/suppliers/create', [SupplierController::class, 'create'])
        ->name('suppliers.create');

    Route::post('/suppliers', [SupplierController::class, 'store'])
        ->name('suppliers.store');

    Route::get('/suppliers/{supplier}/edit', [SupplierController::class, 'edit'])
        ->name('suppliers.edit');

    Route::put('/suppliers/{supplier}', [SupplierController::class, 'update'])
        ->name('suppliers.update');

    Route::delete('/suppliers/{supplier}', [SupplierController::class, 'destroy'])
        ->name('suppliers.destroy');


    // Quick Edit (must come BEFORE routes using /items/{item})
    Route::get('/items/quick-edit', [ItemController::class, 'quickEdit'])
        ->name('items.quick-edit');

    Route::get('/items/quick-edit/list', [ItemController::class, 'quickEditList'])
        ->name('items.quick-edit.list');

    Route::put('/items/{item}/quick-update', [ItemController::class, 'quickUpdate'])
        ->name('items.quick-update');

    // Items - create/edit/delete (admin only)
    Route::get('/items/create', [ItemController::class, 'create'])
        ->name('items.create');


    // Items - create/edit/delete (admin only)
    Route::get('/items/create', [ItemController::class, 'create'])
        ->name('items.create');

    Route::post('/items', [ItemController::class, 'store'])
        ->name('items.store');

    Route::get('/items/{item}/edit', [ItemController::class, 'edit'])
        ->name('items.edit');

    Route::put('/items/{item}', [ItemController::class, 'update'])
        ->name('items.update');

    Route::delete('/items/{item}', [ItemController::class, 'destroy'])
        ->name('items.destroy');



    // Item Suppliers
    Route::get(
        'items/{item}/suppliers',
        [ItemSupplierController::class, 'index']
    )->name('items.suppliers.index');

    Route::post(
        '/items/{item}/suppliers',
        [ItemSupplierController::class, 'store']
    )->name('items.suppliers.store');

    Route::get(
        '/items/{item}/suppliers/{itemSupplier}/edit',
        [ItemSupplierController::class, 'edit']
    )->name('items.suppliers.edit');

    Route::put(
        '/items/{item}/suppliers/{itemSupplier}',
        [ItemSupplierController::class, 'update']
    )->name('items.suppliers.update');

    Route::delete(
        '/items/{item}/suppliers/{itemSupplier}',
        [ItemSupplierController::class, 'destroy']
    )->name('items.suppliers.destroy');


    // ============================================================
    // Global Purchase Planning Overview
    // ============================================================

    Route::get(
        '/purchase-planning',
        [PurchasePlanningController::class, 'overview']
    )->name('purchase-planning.index');


    // ============================================================
    // Item Purchase Planning Settings
    // ============================================================

    Route::get(
        '/items/{item}/purchase-planning/edit',
        [ItemPurchaseSettingController::class, 'edit']
    )->name('items.purchase-planning.edit');

    Route::put(
        '/items/{item}/purchase-planning',
        [ItemPurchaseSettingController::class, 'update']
    )->name('items.purchase-planning.update');


    // ============================================================
    // Detailed Item Purchase Planning
    // ============================================================

    Route::get(
        '/items/{item}/purchase-planning',
        [PurchasePlanningController::class, 'index']
    )->name('items.purchase-planning.index');


    // Calendar View
    Route::get(
        'purchase-calendar',
        [PurchaseCalendarController::class, 'index']
    )->name('purchase-calendar.index');

    Route::get(
        '/purchase-calendar/search-items',
        [PurchaseCalendarController::class, 'searchItems']
    )->name('purchase-calendar.search-items');

    Route::get(
        '/purchase-calendar/search-product-models',
        [PurchaseCalendarController::class, 'searchProductModels']
    )->name('purchase-calendar.search-product-models');


    // Product Models - create/edit/delete (admin only)
    Route::get('/product-models/create', [ProductModelController::class, 'create'])
        ->name('product-models.create');

    Route::post('/product-models', [ProductModelController::class, 'store'])
        ->name('product-models.store');

    Route::get('/product-models/{productModel}/edit', [ProductModelController::class, 'edit'])
        ->name('product-models.edit');

    Route::put('/product-models/{productModel}', [ProductModelController::class, 'update'])
        ->name('product-models.update');

    Route::delete('/product-models/{productModel}', [ProductModelController::class, 'destroy'])
        ->name('product-models.destroy');

    /*
    |--------------------------------------------------------------------------
    | Product Model Items / BOM
    |--------------------------------------------------------------------------
    */


    Route::get(
        '/product-models/{productModel}/items/create',
        [ProductModelItemController::class, 'create']
    )->name('product-models.items.create');


    Route::post(
        '/product-models/{productModel}/items',
        [ProductModelItemController::class, 'store']
    )->name('product-models.items.store');


    Route::get(
        '/product-models/{productModel}/items/{productModelItem}/edit',
        [ProductModelItemController::class, 'edit']
    )->name('product-models.items.edit');


    Route::put(
        '/product-models/{productModel}/items/{productModelItem}',
        [ProductModelItemController::class, 'update']
    )->name('product-models.items.update');


    Route::delete(
        '/product-models/{productModel}/items/{productModelItem}',
        [ProductModelItemController::class, 'destroy']
    )->name('product-models.items.destroy');


    /*
|--------------------------------------------------------------------------
| Production Plan Component Requirements
|--------------------------------------------------------------------------
*/

    Route::get(
        '/production-plans/{productionPlan}/requirements',
        [ProductionPlanController::class, 'requirements']
    )->name('production-plans.requirements');


    /*
    |--------------------------------------------------------------------------
    | Production Plans
    |--------------------------------------------------------------------------
    */

    Route::resource(
        'production-plans',
        ProductionPlanController::class
    )->except(['show']);


    Route::get(
        '/production-plans/{productionPlan}/material-issue',
        [ProductionPlanController::class, 'materialIssue']
    )->name('production-plans.material-issue');

    Route::post(
        '/production-plans/{productionPlan}/material-issue',
        [ProductionPlanController::class, 'postMaterialIssue']
    )->name('production-plans.material-issue.post');
    Route::post(
        '/production-plans/{productionPlan}/material-issue/void',
        [ProductionPlanController::class, 'voidMaterialIssue']
    )->name('production-plans.material-issue.void');

    Route::get(
        '/production-plans/{productionPlan}/material-issue-history',
        [ProductionPlanController::class, 'materialIssueHistory']
    )->name('production-plans.material-issue-history');

    //PURchase order
    Route::resource(
        'purchase-orders',
        PurchaseOrderController::class
    );

    Route::get(
        '/purchase-orders/{purchaseOrder}/items/create',
        [PurchaseOrderItemController::class, 'create']
    )->name('purchase-orders.items.create');


    Route::post(
        '/purchase-orders/{purchaseOrder}/items',
        [PurchaseOrderItemController::class, 'store']
    )->name('purchase-orders.items.store');


    Route::get(
        '/purchase-orders/{purchaseOrder}/items/{purchaseOrderItem}/edit',
        [PurchaseOrderItemController::class, 'edit']
    )->name('purchase-orders.items.edit');


    Route::put(
        '/purchase-orders/{purchaseOrder}/items/{purchaseOrderItem}',
        [PurchaseOrderItemController::class, 'update']
    )->name('purchase-orders.items.update');

    Route::delete(
        '/purchase-orders/{purchaseOrder}/items/{purchaseOrderItem}',
        [PurchaseOrderItemController::class, 'destroy']
    )->name('purchase-orders.items.destroy');

    // Excel upload
    Route::get(
        'purchase-orders/{purchaseOrder}/items/create-upload',
        [PurchaseOrderItemController::class, 'createWithUpload']
    )
        ->name('purchase-orders.items.create-upload');




    Route::post(
        '/purchase-orders/{purchaseOrder}/confirm',
        [PurchaseOrderController::class, 'confirm']
    )->name('purchase-orders.confirm');
    Route::post(
        '/purchase-orders/{purchaseOrder}/cancel',
        [PurchaseOrderController::class, 'cancel']
    )->name('purchase-orders.cancel');

    //PDF generation route for purchase orders
    Route::get(
        '/purchase-orders/{purchaseOrder}/pdf',
        [PurchaseOrderController::class, 'pdf']
    )->name('purchase-orders.pdf');
    Route::get('purchase-orders/{purchaseOrder}/pdf', [PurchaseOrderController::class, 'pdf'])
        ->name('purchase-orders.pdf');


    //Goods RECEIPT

    Route::resource(
        'goods-receipts',
        GoodsReceiptController::class
    );
    //Goods ITEM RECEIPT
    Route::get(
        '/goods-receipts/{goodsReceipt}/items/{purchaseOrderItem}/create',
        [GoodsReceiptItemController::class, 'create']
    )->name('goods-receipts.items.create');


    Route::post(
        '/goods-receipts/{goodsReceipt}/items/{purchaseOrderItem}',
        [GoodsReceiptItemController::class, 'store']
    )->name('goods-receipts.items.store');

    Route::post(
        '/goods-receipts/{goodsReceipt}/void',
        [GoodsReceiptController::class, 'void']
    )->name('goods-receipts.void');

    Route::post(
        'goods-receipts/{goodsReceipt}/purchase-orders',
        [GoodsReceiptController::class, 'attachPurchaseOrder']
    )->name('goods-receipts.purchase-orders.attach');
    
    Route::get(
        'goods-receipts/{goodsReceipt}/usage',
        [GoodsReceiptController::class, 'usageReport']
    )->name('goods-receipts.usage');



    // Manual Purchase Order Product model Items
    Route::get(
        '/purchase-orders/{purchaseOrder}/product-models/{productModel}/items',
        [PurchaseOrderItemController::class, 'itemsByProductModel']
    )->name('purchase-orders.product-model-items');


    //Supplier paynent controller
    Route::get(
        '/supplier-payments',
        [SupplierPaymentController::class, 'index']
    )->name('supplier-payments.index');

    Route::get(
        '/supplier-invoices/{supplierInvoice}/payments/create',
        [SupplierPaymentController::class, 'create']
    )->name('supplier-invoices.payments.create');


    Route::post(
        '/supplier-invoices/{supplierInvoice}/payments',
        [SupplierPaymentController::class, 'store']
    )->name('supplier-invoices.payments.store');


    Route::delete(
        '/supplier-invoices/{supplierInvoice}/payments/{supplierPayment}',
        [SupplierPaymentController::class, 'destroy']
    )->name('supplier-invoices.payments.destroy');

    //ACCOUNT PAYABLE CONTROLLER
    Route::get(
        '/accounts-payable',
        [AccountsPayableController::class, 'index']
    )->name('accounts-payable.index');

    //Supplier Ledger route
    Route::get(
        '/supplier-ledger',
        [SupplierLedgerController::class, 'index']
    )->name('supplier-ledger.index');


    // Purchase Return Rote
    Route::get(
        '/purchase-returns',
        [
            PurchaseReturnController::class,
            'index'
        ]
    )->name('purchase-returns.index');


    Route::get(
        '/purchase-returns/{purchaseReturn}',
        [
            PurchaseReturnController::class,
            'show'
        ]
    )->name('purchase-returns.show');
    Route::get(
        '/goods-receipts/{goodsReceipt}/purchase-return/create',
        [PurchaseReturnController::class, 'create']
    )->name('purchase-returns.create');

    Route::post(
        '/goods-receipts/{goodsReceipt}/purchase-return',
        [PurchaseReturnController::class, 'store']
    )->name('purchase-returns.store');

    Route::post(
        '/purchase-returns/{purchaseReturn}/post',
        [
            PurchaseReturnController::class,
            'post'
        ]
    )->name('purchase-returns.post');
    Route::post(
        '/purchase-returns/{purchaseReturn}/cancel',
        [
            PurchaseReturnController::class,
            'cancel'
        ]
    )->name('purchase-returns.cancel');


    //REPORTING CONTROLLER
    Route::get('/reporting', [ReportingController::class, 'index'])
        ->name('reporting.index');

    Route::get('/reporting/items/{productModel}', [ReportingController::class, 'itemsByModel'])
        ->name('reporting.items-by-model');

    Route::get('/reporting/po-tracking', [ReportingController::class, 'poTrackingIndex'])
        ->name('reporting.po-tracking.index');

    Route::get('/reporting/po-tracking/items/{purchaseOrder}', [ReportingController::class, 'poTrackingItems'])
        ->name('reporting.po-tracking.items');
    Route::get('/reporting/po-tracking/item-history/{poItem}', [App\Http\Controllers\ReportingController::class, 'poItemHistory'])
        ->name('reporting.po-tracking.item-history');


            Route::get('/reporting/grn-usage', [GoodsReceiptController::class, 'usageIndex'])
        ->name('reporting.grn-usage.index');

    Route::get('/reporting/grn-usage/{goodsReceipt}', [GoodsReceiptController::class, 'usageReport'])
        ->name('reporting.grn-usage.show');
            Route::get('/reporting/model-grn-usage', [GoodsReceiptController::class, 'modelUsage'])
        ->name('reporting.model-grn-usage');


});
