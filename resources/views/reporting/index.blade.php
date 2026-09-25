@extends('layouts.admin')

@section('title', 'Product Model Report')
@push('styles')
    <style>
        #reportingTable {
            font-size: 0.75rem;
        }
    </style>
@endpush

@section('content')

    <div class="container-fluid py-3">

        {{-- Header --}}
        <div class="d-flex justify-content-between align-items-center mb-3">

            <div>

                <h3 class="mb-1">
                    <i class="fas fa-boxes mr-2"></i>
                    Product Model Report
                </h3>

                <p class="text-muted mb-0">
                    Select a Product Model to view its items and preferred supplier pricing
                </p>

            </div>

        </div>


        {{-- Product Model Filter --}}
        <div class="card">

            <div class="card-body">

                <div class="row">

                    <div class="col-md-4">

                        <div class="form-group mb-0">

                            <label>
                                Product Model
                            </label>

                            <select id="product_model_id" class="form-control">

                                <option value="">
                                    -- Select Product Model --
                                </option>

                                @foreach ($productModels as $productModel)
                                    <option value="{{ $productModel->id }}">

                                        {{ $productModel->model_code }}

                                        @if (!empty($productModel->model_name ?? null))
                                            -
                                            {{ $productModel->model_name }}
                                        @endif

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

                <table id="reportingTable" class="table table-bordered table-striped table-sm">

                    <thead>
                        <tr>
                            <th>SL</th>
                            <th>Model Code</th>
                            <th>Item Code</th>
                            <th>Item Name</th>
                            <th>Old Part No</th>
                            <th>Revision</th>
                            <th>Qty</th>
                            <th>Supplier</th>
                            <th>Unit Price</th>
                            <th>Lead Time (days)</th>
                            <th>Min Order Qty</th>
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

            var productModelSelect =
                $('#product_model_id');


            /*
            |--------------------------------------------------------------------------
            | Init DataTable (empty until a model is selected)
            |--------------------------------------------------------------------------
            */

            var table = $('#reportingTable').DataTable({
                dom: 'Bfrtip',
                data: [],
                columns: [{
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
                        data: 'old_part_no'
                    },
                    {
                        data: 'revision'
                    },
                    {
                        data: 'quantity'
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
                        data: 'unit_price'
                    },
                    {
                        data: 'lead_time_days'
                    },
                    {
                        data: 'minimum_order_qty'
                    }
                ],
                buttons: [{
                        extend: 'excelHtml5',
                        text: '<i class="fas fa-file-excel mr-1"></i> Excel',
                        className: 'btn btn-success btn-sm',
                        title: 'Product Model Report'
                    },
                    {
                        extend: 'pdfHtml5',
                        text: '<i class="fas fa-file-pdf mr-1"></i> PDF',
                        className: 'btn btn-danger btn-sm',
                        title: 'Product Model Report',
                        orientation: 'landscape',
                        pageSize: 'A4',
                        exportOptions: {
                            columns: ':visible'
                        }
                    },
                    {
                        extend: 'csvHtml5',
                        text: '<i class="fas fa-file-csv mr-1"></i> CSV',
                        className: 'btn btn-secondary btn-sm',
                        title: 'Product Model Report'
                    },
                    {
    extend: 'print',
    text: '<i class="fas fa-print mr-1"></i> Print',
    className: 'btn btn-info btn-sm',
    orientation: 'landscape',
    pageSize: 'A4',
    customize: function (win) {

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
            | Load items when Product Model changes
            |--------------------------------------------------------------------------
            */

            function loadItemsByProductModel() {

                var productModelId =
                    productModelSelect.val();

                table.clear().draw();

                if (!productModelId) {
                    return;
                }

                var url =
                    '{{ route('reporting.items-by-model', ':productModel') }}';

                url = url.replace(
                    ':productModel',
                    productModelId
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
                            'Unable to load items for this model.'
                        );
                    }

                });
            }


            productModelSelect.on(
                'change',
                loadItemsByProductModel
            );

        });
    </script>
@endpush
