<?php

namespace App\Http\Controllers;

use App\Models\GoodsReceipt;
use App\Models\Supplier;
use Illuminate\Http\Request;

class AccountsPayableController extends Controller
{
    public function index(Request $request)
    {
        $supplierId = $request->input('supplier_id');
        $status     = $request->input('status');
        $fromDate   = $request->input('from_date');
        $toDate     = $request->input('to_date');

        $suppliers = Supplier::where('status', true)
            ->orderBy('name')
            ->get();

        // Only posted (not voided) goods receipts
        $query = GoodsReceipt::with(['supplier', 'purchaseOrders'])
            ->where('status', '!=', 'voided');

        if ($supplierId) {
            $query->where('supplier_id', $supplierId);
        }

        if ($status && in_array($status, ['unpaid', 'partially_paid', 'paid'])) {
            $query->where('payment_status', $status);
        }

        if ($fromDate) {
            $query->whereDate('receipt_date', '>=', $fromDate);
        }

        if ($toDate) {
            $query->whereDate('receipt_date', '<=', $toDate);
        }

        // Still named $invoices so the view variable does not change yet
        $invoices = $query
            ->orderByDesc('receipt_date')
            ->orderByDesc('id')
            ->get();

        $supplierSummary = $invoices
            ->groupBy('supplier_id')
            ->map(function ($receipts) {
                return [
                    'supplier'       => $receipts->first()->supplier,
                    'total_invoiced' => (float) $receipts->sum('total_amount'),
                    'total_paid'     => (float) $receipts->sum('paid_amount'),
                    'outstanding'    => (float) $receipts->sum('balance_amount'),
                    'overdue'        => 0, // receipts have no due date
                    'open_invoices'  => $receipts
                        ->filter(fn($r) => (float) $r->balance_amount > 0)
                        ->count(),
                ];
            })
            ->values();

        $summary = [
            'total_invoiced'     => (float) $invoices->sum('total_amount'),
            'total_paid'         => (float) $invoices->sum('paid_amount'),
            'total_outstanding'  => (float) $invoices->sum('balance_amount'),
            'total_overdue'      => 0,
            'open_invoice_count' => $invoices
                ->filter(fn($r) => (float) $r->balance_amount > 0)
                ->count(),
        ];

        return view(
            'accounts-payable.index',
            compact(
                'suppliers',
                'supplierId',
                'status',
                'fromDate',
                'toDate',
                'invoices',
                'supplierSummary',
                'summary'
            )
        );
    }
}