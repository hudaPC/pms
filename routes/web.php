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
| Protected Application Routes
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
    | Master Data
    |--------------------------------------------------------------------------
    */

    // Categories
    Route::resource('categories', CategoryController::class)
        ->except(['show']);

    // Units
    Route::resource('units', UnitController::class)
        ->except(['show']);

    // Items
    // Select2 AJAX
    Route::get('/items-search/product-models', [ItemController::class, 'searchProductModels'])
        ->name('items.search.product-models');

    Route::get('/items-search/item-codes', [ItemController::class, 'searchItemCodes'])
        ->name('items.search.item-codes');
    Route::resource('items', ItemController::class)
        ->except(['show']);

    // Suppliers
    Route::resource('suppliers', SupplierController::class)
        ->except(['show']);

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


    // Item Purchase Planning (Settings form)

    Route::get(
        '/items/{item}/purchase-planning/edit',
        [ItemPurchaseSettingController::class, 'edit']
    )->name('items.purchase-planning.edit');

    Route::put(
        '/items/{item}/purchase-planning',
        [ItemPurchaseSettingController::class, 'update']
    )->name('items.purchase-planning.update');


    // Purchase Planning (Calendar / summary view)

    Route::get(
        '/items/{item}/purchase-planning',
        [PurchasePlanningController::class, 'index']
    )->name('items.purchase-planning.index');


    // Usage Logs
    Route::resource(
        'items.usage-logs',
        UsageLogController::class
    )->except(['show']);


    Route::get(
        '/items/{item}/usage-logs/next-reference',
        [UsageLogController::class, 'nextReference']
    )->name('items.usage-logs.next-reference');



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



    // PRoduct Models
    // Product Models
    Route::resource(
        'product-models',
        ProductModelController::class
    )->except(['show']);

    /*
    |--------------------------------------------------------------------------
    | Product Model Items / BOM
    |--------------------------------------------------------------------------
    */

    Route::get(
        '/product-models/{productModel}/items',
        [ProductModelItemController::class, 'index']
    )->name('product-models.items.index');


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

    Route::post(
        '/purchase-orders/{purchaseOrder}/confirm',
        [PurchaseOrderController::class, 'confirm']
    )->name('purchase-orders.confirm');
    Route::post(
        '/purchase-orders/{purchaseOrder}/cancel',
        [PurchaseOrderController::class, 'cancel']
    )->name('purchase-orders.cancel');

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
// Manual Purchase Order Product model Items
Route::get(
    '/purchase-orders/{purchaseOrder}/product-models/{productModel}/items',
    [PurchaseOrderItemController::class, 'itemsByProductModel']
)->name('purchase-orders.product-model-items');

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
});
