@extends('layouts.admin')

@section('title', 'Purchase Planning')

@section('content')

    <div class="container-fluid">

        <div class="card card-primary card-outline">

            <div class="card-header">

                <h3 class="card-title">
                    <i class="fas fa-chart-line mr-1"></i>
                    Purchase Planning
                </h3>

            </div>

            <div class="card-body">

                <p class="text-muted">
                    Global Purchase Planning Overview
                </p>

                {{-- ============================================================ --}}
                {{-- Search & Filter --}}
                {{-- ============================================================ --}}

                <form method="GET" action="{{ route('purchase-planning.index') }}" class="mb-4">

                    <div class="row">

                        {{-- Product Model --}}
                        <div class="col-md-5">

                            <div class="form-group">

                                <label>
                                    Product Model
                                </label>

                                <select name="product_model_id" class="form-control">

                                    <option value="">
                                        All Product Models
                                    </option>

                                    @foreach ($productModels as $productModel)
                                        <option value="{{ $productModel->id }}"
                                            {{ request('product_model_id') == $productModel->id ? 'selected' : '' }}>
                                            {{ $productModel->model_code }}
                                        </option>
                                    @endforeach

                                </select>

                            </div>

                        </div>


                        {{-- Item Search --}}
                        <div class="col-md-5">

                            <div class="form-group">

                                <label>
                                    Item Code / Name
                                </label>

                                <input type="text" name="search" class="form-control" value="{{ request('search') }}"
                                    placeholder="Search Item Code or Item Name...">

                            </div>

                        </div>


                        {{-- Buttons --}}
                        <div class="col-md-2">

                            <div class="form-group">

                                <label>&nbsp;</label>

                                <div class="d-flex">

                                    <button type="submit" class="btn btn-primary mr-2" title="Search">
                                        <i class="fas fa-search"></i>
                                    </button>

                                    <a href="{{ route('purchase-planning.index') }}" class="btn btn-secondary"
                                        title="Reset">
                                        <i class="fas fa-undo"></i>
                                    </a>

                                </div>

                            </div>

                        </div>

                    </div>

                </form>


                <div class="table-responsive">

                    <table class="table table-bordered table-hover">

                        <thead>
                            <tr>
                                <th style="width: 60px;">#</th>
                                <th>Product Model</th>
                                <th>Item Code</th>
                                <th>Item Name</th>
                                <th>Purchase Settings</th>
                                <th class="text-right">Current Stock</th>
                                <th class="text-right">On Order</th>
                                <th class="text-right">Inventory Position</th>
                                <th style="width: 180px;">Action</th>
                            </tr>
                        </thead>

                        <tbody>

                            @forelse($items as $item)
                                <tr>

                                    <td>
                                        {{ $items->firstItem() + $loop->index }}
                                    </td>

                                    <td>
                                        {{ $item->productModel->model_code ?? '-' }}
                                    </td>

                                    <td>
                                        <strong>{{ $item->item_code }}</strong>
                                    </td>

                                    <td>
                                        {{ $item->item_name }}
                                    </td>

                                    <td>

                                        @if ($item->purchaseSettings)
                                            <span class="badge badge-success">
                                                Configured
                                            </span>
                                        @else
                                            <span class="badge badge-warning">
                                                Settings Required
                                            </span>
                                        @endif

                                    </td>
                                    {{-- Current Stock --}}
                                    <td class="text-right">

                                        @if ($item->planning_summary['has_settings'] ?? false)
                                            {{ number_format($item->planning_summary['current_stock'] ?? 0, 3) }}
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif

                                    </td>


                                    {{-- On Order --}}
                                    <td class="text-right">

                                        @if ($item->planning_summary['has_settings'] ?? false)
                                            {{ number_format($item->planning_summary['on_order_quantity'] ?? 0, 3) }}
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif

                                    </td>


                                    {{-- Inventory Position --}}
                                    <td class="text-right">

                                        @if ($item->planning_summary['has_settings'] ?? false)
                                            <strong>
                                                {{ number_format($item->planning_summary['inventory_position'] ?? 0, 3) }}
                                            </strong>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif

                                    </td>

                                    <td>

                                        @if ($item->purchaseSettings)
                                            <a href="{{ route('items.purchase-planning.index', $item) }}"
                                                class="btn btn-sm btn-primary">
                                                <i class="fas fa-chart-area mr-1"></i>
                                                Open Planning
                                            </a>
                                        @else
                                            <a href="{{ route('items.purchase-planning.edit', $item) }}"
                                                class="btn btn-sm btn-warning">
                                                <i class="fas fa-cogs mr-1"></i>
                                                Configure
                                            </a>
                                        @endif

                                    </td>

                                </tr>

                            @empty

                                <tr>
                                    <td colspan="9" class="text-center text-muted">
                                        No active items found.
                                    </td>
                                </tr>
                            @endforelse

                        </tbody>

                    </table>

                </div>

                <div class="mt-3">
                    {{ $items->links('pagination::bootstrap-5') }}
                </div>

            </div>

        </div>

    </div>

@endsection
