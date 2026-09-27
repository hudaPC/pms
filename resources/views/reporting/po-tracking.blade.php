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
    <script src="https://cdn.jsdelivr.net/npm/xlsx@0.18.5/dist/xlsx.full.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/jspdf@2.5.1/dist/jspdf.umd.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/jspdf-autotable@3.8.2/dist/jspdf.plugin.autotable.min.js"></script>
    </script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {

            var purchaseOrderSelect =
                $('#purchase_order_id');

            function exportPdfWithHistory(dt) {

                //var rows = dt.rows({ page: 'current' }).data();
                var rows = dt.rows().data();

                if (rows.length === 0) {
                    toastr.error('No data to export.');
                    return;
                }

                toastr.info('Preparing PDF, please wait...');

                var requests = [];

                rows.each(function (rowData) {

                    var url = '{{ route('reporting.po-tracking.item-history', ':poItemId') }}';
                    url = url.replace(':poItemId', rowData.po_item_id);

                    requests.push(
                        $.ajax({ url: url, type: 'GET' })
                    );

                });

                $.when.apply($, requests).done(function () {

                    var responses = requests.length === 1 ? [arguments] : Array.prototype.slice.call(arguments);

                    var doc = new window.jspdf.jsPDF({
                        orientation: 'landscape',
                        unit: 'mm',
                        format: 'a4'
                    });

                    var poText = purchaseOrderSelect.find('option:selected').text();
                    var pageHeight = doc.internal.pageSize.getHeight();
                    var marginLeft = 10;

                    doc.setFontSize(12);
                    doc.text('PO Tracking Report', marginLeft, 10);
                    doc.setFontSize(9);
                    doc.text('Purchase Order: ' + poText, marginLeft, 16);

                    var mainHead = [
                        'SL', 'Model Code', 'Item Code', 'Item Name', 'Supplier',
                        'Ordered Qty', 'Received Qty', 'Pending Receipt',
                        'Issued Qty', 'Returned Qty', 'Balance in Stock'
                    ];

                    var currentY = 20;

                    // Helper: start a new page if we're too close to the bottom
                    function ensureSpace(neededHeight) {

                        if (currentY + neededHeight > pageHeight - 10) {
                            doc.addPage();
                            currentY = 15;
                        }
                    }

                    // Helper: draw one small history sub-table with its own heading
                    function drawHistoryTable(label, head, body) {

                        ensureSpace(12);

                        doc.setFontSize(7);
                        doc.setTextColor(150, 0, 0);
                        doc.text(label, marginLeft + 3, currentY + 3);
                        currentY += 4;

                        doc.autoTable({
                            startY: currentY,
                            head: [head],
                            body: body,
                            styles: {
                                fontSize: 6,
                                cellPadding: 1
                            },
                            headStyles: {
                                fillColor: [245, 245, 245],
                                textColor: [0, 0, 0],
                                fontSize: 6
                            },
                            margin: {
                                left: marginLeft + 3,
                                right: marginLeft
                            }
                        });

                        currentY = doc.lastAutoTable.finalY + 2;
                        doc.setTextColor(0, 0, 0);
                    }

                    rows.each(function (d, i) {

                        ensureSpace(12);

                        // Main item row as its own small table
                        doc.autoTable({
                            startY: currentY,
                            head: [mainHead],
                            body: [
                                [i + 1, d.model_code || '', d.item_code || '', d.item_name || '',
                                d.supplier_name || '', d.ordered_quantity || 0, d.received_quantity || 0,
                                d.pending_receipt || 0, d.issued_quantity || 0, d.returned_quantity || 0,
                                d.balance_in_stock || 0
                                ]
                            ],
                            styles: {
                                fontSize: 7,
                                cellPadding: 1.5,
                                fontStyle: 'bold'
                            },
                            headStyles: {
                                fillColor: [230, 230, 230],
                                textColor: [0, 0, 0],
                                fontStyle: 'bold',
                                fontSize: 7
                            },
                            margin: {
                                left: marginLeft,
                                right: marginLeft
                            }
                        });

                        currentY = doc.lastAutoTable.finalY + 1;

                        var h = responses[i] ? responses[i][0] : null;

                        if (h) {

                            var receiptBody = (h.receipts && h.receipts.length > 0) ?
                                h.receipts.map(function (r) {
                                    return [r.grn_number || '', r.receipt_date || '', r.received_quantity || '', r.remarks || ''];
                                }) :
                                [
                                    ['No receipts found', '', '', '']
                                ];

                            drawHistoryTable(
                                'Receiving History',
                                ['GRN No', 'Receipt Date', 'Received Qty', 'Remarks'],
                                receiptBody
                            );

                            var issueBody = (h.issues && h.issues.length > 0) ?
                                h.issues.map(function (u) {
                                    return [u.usage_date || '', u.quantity || '', u.reference || '', u.remarks || ''];
                                }) :
                                [
                                    ['No issues found', '', '', '']
                                ];

                            drawHistoryTable(
                                'Issuing History',
                                ['Usage Date', 'Quantity', 'Reference', 'Remarks'],
                                issueBody
                            );

                            var returnBody = (h.returns && h.returns.length > 0) ?
                                h.returns.map(function (r) {
                                    return [r.return_number || '', r.return_date || '', r.return_quantity || '', r.reason || '', r.remarks || ''];
                                }) :
                                [
                                    ['No returns found', '', '', '', '']
                                ];

                            drawHistoryTable(
                                'Return History',
                                ['Return No', 'Return Date', 'Return Qty', 'Reason', 'Remarks'],
                                returnBody
                            );

                            currentY += 3; // extra gap before next item
                        } else {
                            currentY += 3;
                        }

                    });

                    doc.save('PO_Tracking_Report.pdf');

                }).fail(function () {
                    toastr.error('Unable to load history for export.');
                });

            }
            function exportExcelWithHistory(dt) {

                //var rows = dt.rows({ page: 'current' }).data();
                var rows = dt.rows().data();
                if (rows.length === 0) {
                    toastr.error('No data to export.');
                    return;
                }

                toastr.info('Preparing Excel, please wait...');

                var requests = [];

                rows.each(function (rowData) {

                    var url = '{{ route('reporting.po-tracking.item-history', ':poItemId') }}';
                    url = url.replace(':poItemId', rowData.po_item_id);

                    requests.push(
                        $.ajax({ url: url, type: 'GET' })
                    );

                });

                $.when.apply($, requests).done(function () {

                    var responses = requests.length === 1 ? [arguments] : Array.prototype.slice.call(arguments);

                    // aoa = "array of arrays" - each inner array is one Excel row
                    var aoa = [];

                    aoa.push([
                        'SL', 'Model Code', 'Item Code', 'Item Name', 'Supplier',
                        'Ordered Qty', 'Received Qty', 'Pending Receipt',
                        'Issued Qty', 'Returned Qty', 'Balance in Stock'
                    ]);

                    rows.each(function (d, i) {

                        aoa.push([
                            i + 1,
                            d.model_code || '',
                            d.item_code || '',
                            d.item_name || '',
                            d.supplier_name || '',
                            d.ordered_quantity || 0,
                            d.received_quantity || 0,
                            d.pending_receipt || 0,
                            d.issued_quantity || 0,
                            d.returned_quantity || 0,
                            d.balance_in_stock || 0
                        ]);

                        var h = responses[i] ? responses[i][0] : null;

                        if (h) {

                            // Receiving History
                            aoa.push(['', 'Receiving History']);
                            aoa.push(['', 'GRN No', 'Receipt Date', 'Received Qty', 'Remarks']);

                            if (h.receipts && h.receipts.length > 0) {
                                $.each(h.receipts, function (j, r) {
                                    aoa.push(['', r.grn_number || '', r.receipt_date || '', r.received_quantity || '', r.remarks || '']);
                                });
                            } else {
                                aoa.push(['', 'No receipts found']);
                            }

                            // Issuing History
                            aoa.push(['', 'Issuing History']);
                            aoa.push(['', 'Usage Date', 'Quantity', 'Reference', 'Remarks']);

                            if (h.issues && h.issues.length > 0) {
                                $.each(h.issues, function (j, u) {
                                    aoa.push(['', u.usage_date || '', u.quantity || '', u.reference || '', u.remarks || '']);
                                });
                            } else {
                                aoa.push(['', 'No issues found']);
                            }

                            // Return History
                            aoa.push(['', 'Return History']);
                            aoa.push(['', 'Return No', 'Return Date', 'Return Qty', 'Reason', 'Remarks']);

                            if (h.returns && h.returns.length > 0) {
                                $.each(h.returns, function (j, r) {
                                    aoa.push(['', r.return_number || '', r.return_date || '', r.return_quantity || '', r.reason || '', r.remarks || '']);
                                });
                            } else {
                                aoa.push(['', 'No returns found']);
                            }

                            // Blank spacer row before next item
                            aoa.push([]);
                        }

                    });

                    var worksheet = XLSX.utils.aoa_to_sheet(aoa);
                    var workbook = XLSX.utils.book_new();

                    XLSX.utils.book_append_sheet(workbook, worksheet, 'PO Tracking Report');

                    XLSX.writeFile(workbook, 'PO_Tracking_Report.xlsx');

                }).fail(function () {
                    toastr.error('Unable to load history for export.');
                });

            }

            function printCurrentView(dt) {

                var poText = purchaseOrderSelect.find('option:selected').text();

                //var rows = dt.rows({ page: 'current' }).data();
                var rows = dt.rows().data();
                if (rows.length === 0) {
                    toastr.error('No data to print.');
                    return;
                }

                // Show a quick loading message so the user knows something is happening
                toastr.info('Preparing print, please wait...');

                // Build one AJAX request per row to fetch its history
                var requests = [];

                rows.each(function (rowData) {

                    var url = '{{ route('reporting.po-tracking.item-history', ':poItemId') }}';
                    url = url.replace(':poItemId', rowData.po_item_id);

                    requests.push(
                        $.ajax({ url: url, type: 'GET' })
                    );

                });

                // Wait until ALL history requests finish, then build the print page
                $.when.apply($, requests).done(function () {

                    // When there are multiple requests, arguments is an array of [data, status, xhr] per request
                    // When there's only 1 request, arguments itself is [data, status, xhr]
                    var responses = requests.length === 1 ? [arguments] : Array.prototype.slice.call(arguments);

                    var html = '';
                    html += '<html><head><title>PO Tracking Report</title><style>';
                    html += 'body{font-family:Arial, sans-serif; font-size:8px; color:#000;}';
                    html += 'h3{margin:0 0 2px; font-size:13px;}';
                    html += 'p{margin:0 0 6px; font-size:9px;}';
                    html += 'table{width:100%; border-collapse:collapse; margin-bottom:6px;}';
                    html += 'th, td{border:1px solid #999; padding:2px 3px; text-align:left; line-height:1.2;}';
                    html += 'th{background:#f0f0f0; font-size:8px;}';
                    html += '.child-block{background:#fafafa; padding:3px 6px;}';
                    html += '.child-block strong{display:block; margin:3px 0 1px; font-size:8px;}';
                    html += '.history-table th, .history-table td{font-size:7px; padding:1px 3px;}';
                    html += '.main-table th, .main-table td{font-size:7px; padding:1px 3px; line-height:1.1; font-weight:bold;}';
                    html += 'tr{page-break-inside:avoid;}';
                    html += '@page{size: A4 landscape; margin:6mm;}';
                    html += '</style></head><body>';

                    html += '<h3>PO Tracking Report</h3>';
                    html += '<p>Purchase Order: ' + poText + '</p>';

                    html += '<table class="main-table"><thead><tr>';
                    html += '<th>SL</th><th>Model Code</th><th>Item Code</th><th>Item Name</th><th>Supplier</th>';
                    html += '<th>Ordered Qty</th><th>Received Qty</th><th>Pending Receipt</th>';
                    html += '<th>Issued Qty</th><th>Returned Qty</th><th>Balance in Stock</th>';
                    html += '</tr></thead><tbody>';

                    rows.each(function (d, i) {

                        html += '<tr>' +
                            '<td>' + (i + 1) + '</td>' +
                            '<td>' + (d.model_code || '') + '</td>' +
                            '<td>' + (d.item_code || '') + '</td>' +
                            '<td>' + (d.item_name || '') + '</td>' +
                            '<td>' + (d.supplier_name || '') + '</td>' +
                            '<td>' + (d.ordered_quantity || '') + '</td>' +
                            '<td>' + (d.received_quantity || '') + '</td>' +
                            '<td>' + (d.pending_receipt || '') + '</td>' +
                            '<td>' + (d.issued_quantity || '') + '</td>' +
                            '<td>' + (d.returned_quantity || '') + '</td>' +
                            '<td>' + (d.balance_in_stock || '') + '</td>' +
                            '</tr>';

                        // responses[i][0] is the JSON data returned for this row
                        var historyData = responses[i] ? responses[i][0] : null;

                        if (historyData) {
                            html += '<tr><td colspan="11" class="child-block">' +
                                buildHistoryHtml(historyData) +
                                '</td></tr>';
                        }

                    });

                    html += '</tbody></table>';
                    html += '</body></html>';

                    var win = window.open('', '_blank', 'width=1200,height=800');
                    win.document.open();
                    win.document.write(html);
                    win.document.close();

                    win.onload = function () {
                        win.focus();
                        win.print();
                    };

                }).fail(function () {
                    toastr.error('Unable to load history for printing.');
                });

            }
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

                    $.each(response.receipts, function (i, r) {
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

                    $.each(response.issues, function (i, u) {
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

                    $.each(response.returns, function (i, r) {
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
                    render: function (data, type, row, meta) {
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
                    render: function (data, type, row) {

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
                    render: function (data, type, row) {

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
                    text: '<i class="fas fa-file-excel mr-1"></i> Excel',
                    className: 'btn btn-success btn-sm',
                    action: function (e, dt, node, config) {
                        exportExcelWithHistory(dt);
                    }
                },
                {
                    text: '<i class="fas fa-file-pdf mr-1"></i> PDF',
                    className: 'btn btn-danger btn-sm',
                    action: function (e, dt, node, config) {
                        exportPdfWithHistory(dt);
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
                    text: '<i class="fas fa-print mr-1"></i> Print',
                    className: 'btn btn-info btn-sm',
                    action: function (e, dt, node, config) {
                        printCurrentView(dt);
                    }
                }



                ]
            });


            /*
            |--------------------------------------------------------------------------
            | Expand / collapse child row
            |--------------------------------------------------------------------------
            */

            $('#poTrackingTable tbody').on('click', 'td.details-control', function () {

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

                    success: function (response) {
                        row.child(buildHistoryHtml(response)).show();
                    },

                    error: function () {
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

                    success: function (response) {

                        table.rows.add(
                            response.items || []
                        ).draw();
                    },

                    error: function () {

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