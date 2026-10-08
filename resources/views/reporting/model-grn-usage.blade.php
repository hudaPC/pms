@extends('layouts.admin')

@section('title', 'Model GRN Usage')

@section('content')
<div class="container-fluid py-4">

    <div class="mb-4">
        <h2 class="mb-1">Model GRN Usage</h2>
        <p class="text-muted mb-0">Select a product model to see which Goods Receipts supplied the items it used.</p>
    </div>

    {{-- Filter --}}
    <div class="card card-primary card-outline">
        <div class="card-body">
            <form method="GET" action="{{ route('reporting.model-grn-usage') }}" class="form-inline">

                <select name="product_model_id" class="form-control mr-2 mb-2" required>
                    <option value="">-- Select Product Model --</option>
                    @foreach ($productModels as $pm)
                        <option value="{{ $pm->id }}" @selected(request('product_model_id') == $pm->id)>
                            {{ $pm->model_code }}{{ $pm->model_name ? ' - ' . $pm->model_name : '' }}
                        </option>
                    @endforeach
                </select>

                <label class="mr-1 mb-2">From</label>
                <input type="date" name="date_from" value="{{ request('date_from') }}" class="form-control mr-2 mb-2">

                <label class="mr-1 mb-2">To</label>
                <input type="date" name="date_to" value="{{ request('date_to') }}" class="form-control mr-2 mb-2">

                <div class="custom-control custom-checkbox mr-3 mb-2">
                    <input type="checkbox" class="custom-control-input" id="show_all" name="show_all" value="1"
                        @checked(request()->boolean('show_all'))>
                    <label class="custom-control-label" for="show_all">Also list items with no usage</label>
                </div>

                <button class="btn btn-primary mb-2"><i class="fas fa-search mr-1"></i> Show</button>
            </form>
        </div>
    </div>

    @if ($selected)

        {{-- GRN-linked usage --}}
        <div class="card card-success card-outline">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-link mr-1"></i>
                    {{ $selected->model_code }}: usage by Goods Receipt
                </h3>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered table-sm">
                        <thead class="thead-light">
                            <tr>
                                <th>#</th>
                                <th>Item Code</th>
                                <th>Item Name</th>
                                <th>GRN</th>
                                <th>PO</th>
                                <th class="text-right">GRN Received</th>
                                <th class="text-right">Used by this Model</th>
                                <th class="text-right">Used (all models)</th>
                                <th class="text-right">GRN Remaining</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($rows as $r)
                                <tr>
                                    <td>{{ $loop->iteration }}</td>
                                    <td><strong>{{ $r['item']->item_code ?? '-' }}</strong></td>
                                    <td>{{ $r['item']->item_name ?? '-' }}</td>
                                    <td>
                                        <a href="{{ route('reporting.grn-usage.show', $r['grn_id']) }}">{{ $r['grn'] }}</a>
                                    </td>
                                    <td>{{ $r['po'] }}</td>
                                    <td class="text-right">{{ number_format($r['received'], 3) }}</td>
                                    <td class="text-right text-danger"><strong>{{ number_format($r['by_model'], 3) }}</strong></td>
                                    <td class="text-right">{{ number_format($r['total_used'], 3) }}</td>
                                    <td class="text-right text-success">{{ number_format($r['remaining'], 3) }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="9" class="text-center text-muted py-4">No GRN-linked usage found for this model.</td></tr>
                            @endforelse
                        </tbody>
                        @if ($rows->isNotEmpty())
                            <tfoot>
                                <tr class="font-weight-bold">
                                    <td colspan="6" class="text-right">Total used by this model</td>
                                    <td class="text-right">{{ number_format($rows->sum('by_model'), 3) }}</td>
                                    <td colspan="2"></td>
                                </tr>
                            </tfoot>
                        @endif
                    </table>
                </div>
            </div>
        </div>

        {{-- Unassigned usage --}}
        @if ($unassigned->isNotEmpty())
            <div class="card card-warning card-outline">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-exclamation-triangle mr-1"></i>
                        Unassigned usage (recorded without a GRN)
                    </h3>
                </div>
                <div class="card-body">
                    <table class="table table-bordered table-sm mb-0">
                        <thead class="thead-light">
                            <tr><th>Item Code</th><th>Item Name</th><th class="text-right">Quantity</th></tr>
                        </thead>
                        <tbody>
                            @foreach ($unassigned as $u)
                                <tr>
                                    <td><strong>{{ $u['item']->item_code ?? '-' }}</strong></td>
                                    <td>{{ $u['item']->item_name ?? '-' }}</td>
                                    <td class="text-right">{{ number_format($u['qty'], 3) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif

        {{-- BOM items without usage --}}
        @if ($bomWithoutUsage->isNotEmpty())
            <div class="card card-outline card-secondary">
                <div class="card-header"><h3 class="card-title">BOM items with no usage</h3></div>
                <div class="card-body">
                    <table class="table table-bordered table-sm mb-0">
                        <thead class="thead-light">
                            <tr><th>Item Code</th><th>Item Name</th></tr>
                        </thead>
                        <tbody>
                            @foreach ($bomWithoutUsage as $m)
                                <tr>
                                    <td><strong>{{ $m->item->item_code }}</strong></td>
                                    <td>{{ $m->item->item_name }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif

    @endif

</div>
@endsection