@extends('layouts.admin')

@section('title', 'PO Tracking Report')

@push('styles')
    <style>
        #poTrackingTable {
            font-size: 0.75rem;
        }

        td.details-control {
            text-align: center;
            cursor: pointer;
        }

        .history-table th,
        .history-table td {
            font-size: 0.7rem;
        }
    </style>
@endpush

@section('content')

    <div class="container-fluid py-3">

        {{-- Header --}}
        <div class="d-flex justify-content-between align-items-center mb-3">

            <div>

                <h3 class="mb-1">
                    <i class="fas fa-truck mr-2"></i>
                    PO Tracking Report
                </h3>

                <p class="text-muted mb-0">
                    Ordered, received, issued, and balance quantities per Purchase Order item
                </p>

            </div>

        </div>


        {{-- PO Filter --}}
        <div class="card">

            <div class="card-body">

                <div class="row">

                    <div class="col-md-4">

                        <div class="form-group mb-0">

                            <label>
                                Purchase Order
                            </label>

                            <select id="purchase_order_id" class="form-control">

                                <option value="">
                                    -- Select Purchase Order --
                                </option>

                                @foreach ($purchaseOrders as $po)
                                    <option value="{{ $po->id }}">

                                        {{ $po->po_number }}
                                        ({{ \Carbon\Carbon::parse($po->order_date)->format('d-M-Y') }})
                                        - {{ ucfirst(str_replace('_', ' ', $po->status)) }}

                                    </option>
                                @endforeach

                            </select>

                        </div>

                    </div>

                </div>

            </div>

        </div>


        {{-- Table --}}
        <div class="card mt-3">

            <div class="card-body">

                <table id="poTrackingTable" class="table table-bordered table-striped table-sm">

                    <thead>
                        <tr>
                            <th></th>
                            <th>SL</th>
                            <th>Model Code</th>
                            <th>Item Code</th>
                            <th>Item Name</th>
                            <th>Supplier</th>
                            <th>Ordered Qty</th>
                            <th>Received Qty</th>
                            <th>Pending Receipt</th>
                            <th>Issued Qty</th>
                            <th>Returned Qty</th>
                            <th>Balance in Stock</th>
                        </tr>
                    </thead>

                    <tbody></tbody>

                </table>

            </div>

        </div>

    </div>

@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {

            var purchaseOrderSelect =
                $('#purchase_order_id');


            /*
            |--------------------------------------------------------------------------
            | Build history HTML for a child row
            |--------------------------------------------------------------------------
            */

            function buildHistoryHtml(response) {

                var html = '<div class="p-2">';

                html += '<strong>Receiving History</strong>';
                html += '<table class="table table-bordered table-sm history-table mt-1 mb-3">';
                html +=
                    '<thead><tr><th>GRN No</th><th>Receipt Date</th><th>Received Qty</th><th>Remarks</th></tr></thead>';
                html += '<tbody>';

                if (response.receipts && response.receipts.length > 0) {

                    $.each(response.receipts, function(i, r) {
                        html += '<tr>' +
                            '<td>' + (r.grn_number || '') + '</td>' +
                            '<td>' + (r.receipt_date || '') + '</td>' +
                            '<td>' + (r.received_quantity || '') + '</td>' +
                            '<td>' + (r.remarks || '') + '</td>' +
                            '</tr>';
                    });

                } else {
                    html += '<tr><td colspan="4" class="text-center text-muted">No receipts found</td></tr>';
                }

                html += '</tbody></table>';


                html += '<strong>Issuing History</strong>';
                html += '<table class="table table-bordered table-sm history-table mt-1 mb-0">';
                html +=
                    '<thead><tr><th>Usage Date</th><th>Quantity</th><th>Reference</th><th>Remarks</th></tr></thead>';
                html += '<tbody>';

                if (response.issues && response.issues.length > 0) {

                    $.each(response.issues, function(i, u) {
                        html += '<tr>' +
                            '<td>' + (u.usage_date || '') + '</td>' +
                            '<td>' + (u.quantity || '') + '</td>' +
                            '<td>' + (u.reference || '') + '</td>' +
                            '<td>' + (u.remarks || '') + '</td>' +
                            '</tr>';
                    });

                } else {
                    html += '<tr><td colspan="4" class="text-center text-muted">No issues found</td></tr>';
                }

                html += '</tbody></table>';

                html += '</div>';
                html += '<strong>Return History</strong>';
                html += '<table class="table table-bordered table-sm history-table mt-1 mb-0">';
                html +=
                    '<thead><tr><th>Return No</th><th>Return Date</th><th>Return Qty</th><th>Reason</th><th>Remarks</th></tr></thead>';
                html += '<tbody>';

                if (response.returns && response.returns.length > 0) {

                    $.each(response.returns, function(i, r) {
                        html += '<tr>' +
                            '<td>' + (r.return_number || '') + '</td>' +
                            '<td>' + (r.return_date || '') + '</td>' +
                            '<td>' + (r.return_quantity || '') + '</td>' +
                            '<td>' + (r.reason || '') + '</td>' +
                            '<td>' + (r.remarks || '') + '</td>' +
                            '</tr>';
                    });

                } else {
                    html += '<tr><td colspan="5" class="text-center text-muted">No returns found</td></tr>';
                }

                html += '</tbody></table>';

                return html;
            }


            /*
            |--------------------------------------------------------------------------
            | Init DataTable
            |--------------------------------------------------------------------------
            */

            var table = $('#poTrackingTable').DataTable({
                dom: 'Bfrtip',
                data: [],
                columns: [{
                        className: 'details-control',
                        orderable: false,
                        searchable: false,
                        data: null,
                        defaultContent: '<i class="fas fa-plus-circle text-primary"></i>'
                    },
                    {
                        data: null,
                        orderable: false,
                        searchable: false,
                        render: function(data, type, row, meta) {
                            return meta.row + 1;
                        }
                    },
                    {
                        data: 'model_code'
                    },
                    {
                        data: 'item_code'
                    },
                    {
                        data: 'item_name',
                        render: function(data, type, row) {

                            if (type !== 'display' || !data) {
                                return data;
                            }

                            var words = data.split(' ');

                            var short = words.length > 2 ?
                                words.slice(0, 2).join(' ') + '...' :
                                data;

                            return '<span title="' + data.replace(/"/g, '&quot;') + '">' + short +
                                '</span>';
                        }
                    },
                    {
                        data: 'supplier_name',
                        render: function(data, type, row) {

                            if (type !== 'display' || !data) {
                                return data;
                            }

                            var words = data.split(' ');

                            var short = words.length > 2 ?
                                words.slice(0, 2).join(' ') + '...' :
                                data;

                            return '<span title="' + data.replace(/"/g, '&quot;') + '">' + short +
                                '</span>';
                        }
                    },
                    {
                        data: 'ordered_quantity'
                    },
                    {
                        data: 'received_quantity'
                    },
                    {
                        data: 'pending_receipt'
                    },
                    {
                        data: 'issued_quantity'
                    },
                    {
                        data: 'returned_quantity'
                    },
                    {
                        data: 'balance_in_stock'
                    }
                ],
                buttons: [{
                        extend: 'excelHtml5',
                        text: '<i class="fas fa-file-excel mr-1"></i> Excel',
                        className: 'btn btn-success btn-sm',
                        title: 'PO Tracking Report',
                        exportOptions: {
                            columns: ':visible:not(.details-control)'
                        }
                    },
                    {
                        extend: 'pdfHtml5',
                        text: '<i class="fas fa-file-pdf mr-1"></i> PDF',
                        className: 'btn btn-danger btn-sm',
                        title: 'PO Tracking Report',
                        orientation: 'landscape',
                        pageSize: 'A4',
                        exportOptions: {
                            columns: ':visible:not(.details-control)'
                        }
                    },
                    {
                        extend: 'csvHtml5',
                        text: '<i class="fas fa-file-csv mr-1"></i> CSV',
                        className: 'btn btn-secondary btn-sm',
                        title: 'PO Tracking Report',
                        exportOptions: {
                            columns: ':visible:not(.details-control)'
                        }
                    },
                    {
                        extend: 'print',
                        text: '<i class="fas fa-print mr-1"></i> Print',
                        className: 'btn btn-info btn-sm',
                        orientation: 'landscape',
                        pageSize: 'A4',
                        exportOptions: {
                            columns: ':visible:not(.details-control)'
                        },
                        customize: function(win) {

                            $(win.document.head).append(
                                '<style>' +
                                '@page { size: A4 landscape; margin: 10mm; }' +
                                'table { width: 100% !important; font-size: 11px; }' +
                                'th, td { padding: 4px !important; }' +
                                '</style>'
                            );
                        }
                    }
                ]
            });


            /*
            |--------------------------------------------------------------------------
            | Expand / collapse child row
            |--------------------------------------------------------------------------
            */

            $('#poTrackingTable tbody').on('click', 'td.details-control', function() {

                var tr = $(this).closest('tr');
                var row = table.row(tr);
                var icon = $(this).find('i');

                if (row.child.isShown()) {

                    row.child.hide();
                    tr.removeClass('shown');
                    icon.removeClass('fa-minus-circle').addClass('fa-plus-circle');

                    return;
                }

                var rowData = row.data();

                row.child('<div class="text-center p-2 text-muted">Loading...</div>').show();
                tr.addClass('shown');
                icon.removeClass('fa-plus-circle').addClass('fa-minus-circle');

                var url =
                    '{{ route('reporting.po-tracking.item-history', ':poItemId') }}';

                url = url.replace(
                    ':poItemId',
                    rowData.po_item_id
                );

                $.ajax({

                    url: url,
                    type: 'GET',

                    success: function(response) {
                        row.child(buildHistoryHtml(response)).show();
                    },

                    error: function() {
                        row.child('<div class="text-danger p-2">Unable to load history.</div>')
                            .show();
                    }

                });

            });


            /*
            |--------------------------------------------------------------------------
            | Load items when PO changes
            |--------------------------------------------------------------------------
            */

            function loadItemsByPo() {

                var purchaseOrderId =
                    purchaseOrderSelect.val();

                table.clear().draw();

                if (!purchaseOrderId) {
                    return;
                }

                var url =
                    '{{ route('reporting.po-tracking.items', ':purchaseOrder') }}';

                url = url.replace(
                    ':purchaseOrder',
                    purchaseOrderId
                );

                $.ajax({

                    url: url,
                    type: 'GET',

                    success: function(response) {

                        table.rows.add(
                            response.items || []
                        ).draw();
                    },

                    error: function() {

                        toastr.error(
                            'Unable to load items for this Purchase Order.'
                        );
                    }

                });
            }


            purchaseOrderSelect.on(
                'change',
                loadItemsByPo
            );

        });
    </script>
@endpush
