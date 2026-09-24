@extends('layouts.admin')

@section('title', 'Product Model Report')

@push('styles')
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap4.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.2/css/buttons.bootstrap4.min.css">
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
                    Items, parts, and preferred supplier pricing for the selected model
                </p>

            </div>

        </div>


        {{-- Table --}}
        <div class="card">

            <div class="card-body">

                <table id="reportingTable" class="table table-bordered table-striped">

                    <thead>
                        <tr>
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

                    <tbody>
                        @foreach ($items as $item)
                            <tr>
                                <td>{{ $item->item_code }}</td>
                                <td>{{ $item->item_name }}</td>
                                <td>{{ $item->old_part_no }}</td>
                                <td>{{ $item->revision }}</td>
                                <td>{{ $item->quantity }}</td>
                                <td>{{ $item->supplier_name }}</td>
                                <td>{{ $item->unit_price }}</td>
                                <td>{{ $item->lead_time_days }}</td>
                                <td>{{ $item->minimum_order_qty }}</td>
                            </tr>
                        @endforeach
                    </tbody>

                </table>

            </div>

        </div>

    </div>

@endsection

@push('scripts')
    {{-- DataTables core --}}
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap4.min.js"></script>

    {{-- Buttons extension + dependencies for Excel/PDF/Print --}}
    <script src="https://cdn.datatables.net/buttons/2.4.2/js/dataTables.buttons.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.bootstrap4.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.html5.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.print.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.colVis.min.js"></script>

    <script>
        $(function () {
            $('#reportingTable').DataTable({
                dom: 'Bfrtip',
                buttons: [
                    {
                        extend: 'excelHtml5',
                        text: '<i class="fas fa-file-excel mr-1"></i> Excel',
                        className: 'btn btn-success btn-sm',
                        title: 'Product Model Report'
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
                        className: 'btn btn-info btn-sm'
                    }
                ]
            });
        });
    </script>
@endpush