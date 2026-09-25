<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReportingController extends Controller
{
    public function index()
    {
        $productModels = DB::table('product_models')
            ->orderBy('model_code')
            ->get();

        return view('reporting.index', compact('productModels'));
    }

    public function itemsByModel(int $productModelId)
    {
        $items = DB::select("
        SELECT
            pm.model_code,
            i.item_code,
            i.item_name,
            pmi.old_part_no,
            pmi.revision,
            pmi.quantity,
            s.name AS supplier_name,
            isup.unit_price,
            isup.lead_time_days,
            isup.minimum_order_qty
        FROM product_models pm
        INNER JOIN product_model_items pmi
            ON pmi.product_model_id = pm.id
        INNER JOIN items i
            ON i.id = pmi.item_id
        LEFT JOIN item_suppliers isup
            ON isup.id = (
                SELECT isup2.id
                FROM item_suppliers isup2
                WHERE isup2.item_id = i.id
                  AND isup2.is_preferred = 1
                  AND isup2.status = 1
                ORDER BY isup2.id
                LIMIT 1
            )
        LEFT JOIN suppliers s
            ON s.id = isup.supplier_id
        WHERE pm.id = ?
        ORDER BY i.item_code
    ", [$productModelId]);

        return response()->json(['items' => $items]);
    }
    public function poTrackingIndex()
    {
        $purchaseOrders = DB::table('purchase_orders')
            ->orderByDesc('order_date')
            ->get();

        return view('reporting.po-tracking', compact('purchaseOrders'));
    }

    public function poTrackingItems(int $purchaseOrder)
    {
        $items = DB::select("
        SELECT
            pm.model_code,
            poi.id AS po_item_id,
            i.item_code,
            i.item_name,
            s.name AS supplier_name,
            poi.ordered_quantity,
            poi.received_quantity,
            (poi.ordered_quantity - poi.received_quantity) AS pending_receipt,
            COALESCE(ul.total_issued, 0) AS issued_quantity,
            (poi.received_quantity - COALESCE(ul.total_issued, 0)) AS balance_in_stock
        FROM purchase_order_items poi
        INNER JOIN items i
            ON i.id = poi.item_id
        INNER JOIN purchase_orders po
            ON po.id = poi.purchase_order_id
        LEFT JOIN suppliers s
            ON s.id = po.supplier_id
        LEFT JOIN product_models pm
            ON pm.id = poi.product_model_id
        LEFT JOIN (
            SELECT item_id, SUM(quantity) AS total_issued
            FROM usage_logs
            WHERE status = 'active'
            GROUP BY item_id
        ) ul ON ul.item_id = poi.item_id
        WHERE poi.purchase_order_id = ?
        ORDER BY i.item_code
    ", [$purchaseOrder]);

        return response()->json(['items' => $items]);
    }
    public function poItemHistory(int $poItem)
    {
        $poItemRow = DB::table('purchase_order_items')->where('id', $poItem)->first();

        if (!$poItemRow) {
            return response()->json(['message' => 'Not found'], 404);
        }

        $receipts = DB::select("
        SELECT
            gr.grn_number,
            gr.receipt_date,
            gri.received_quantity,
            gri.remarks
        FROM goods_receipt_items gri
        INNER JOIN goods_receipts gr
            ON gr.id = gri.goods_receipt_id
        WHERE gri.purchase_order_item_id = ?
          AND gr.status = 'posted'
        ORDER BY gr.receipt_date
    ", [$poItem]);

        $issues = DB::select("
        SELECT
            usage_date,
            quantity,
            reference,
            remarks
        FROM usage_logs
        WHERE item_id = ?
          AND status = 'active'
        ORDER BY usage_date
    ", [$poItemRow->item_id]);

        return response()->json([
            'receipts' => $receipts,
            'issues' => $issues,
        ]);
    }
}
