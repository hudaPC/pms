<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReportingController extends Controller
{
    public function index()
    {
        // For now this is hardcoded to one model.
        // Once this works, we'll turn it into a dropdown/search filter.
        $modelCode = 'VS-0814H1';

        $items = DB::select("
            SELECT
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
                ON isup.item_id = i.id
               AND isup.is_preferred = 1
               AND isup.status = 1
            LEFT JOIN suppliers s
                ON s.id = isup.supplier_id
            WHERE pm.model_code = ?
            ORDER BY i.item_code
        ", [$modelCode]);

        return view('reporting.index', compact('items'));
    }
}