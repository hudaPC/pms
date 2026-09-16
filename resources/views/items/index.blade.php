@extends('layouts.admin')

@section('title', 'Items')

@section('content')

    <div class="container-fluid">

        {{-- Page Header --}}
        <div class="row mb-3 align-items-end">

            {{-- Title --}}
            <div class="col-md-3">

                <h1 class="m-0">
                    Items
                </h1>

                <small class="text-muted">
                    Manage items and item master information
                </small>

            </div>


            {{-- Search / Filter --}}
           <div class="col-md-9">

    <form action="{{ route('items.index') }}"
          method="GET"
          id="itemFilterForm">

        <div class="row justify-content-end align-items-end">

            {{-- Product Model --}}
            <div class="col-md-4">

                <label class="small mb-1">
                    Product Model
                </label>

                <select name="product_model_id"
                        id="filter_product_model_id"
                        class="form-control no-select2">

                    @if(request('product_model_id'))
                        <option value="{{ request('product_model_id') }}" selected>
                            {{ request('product_model_text', 'Selected Product Model') }}
                        </option>
                    @endif

                </select>

            </div>


            {{-- Item Code --}}
            <div class="col-md-4">

                <label class="small mb-1">
                    Item Code
                </label>

                <select name="item_id"
                        id="filter_item_id"
                        class="form-control no-select2">

                    @if(request('item_id'))
                        <option value="{{ request('item_id') }}" selected>
                            {{ request('item_text', 'Selected Item') }}
                        </option>
                    @endif

                </select>

            </div>


            {{-- Search --}}
            <div class="col-auto">

                <button type="submit"
                        class="btn btn-primary"
                        title="Search">

                    <i class="fas fa-search"></i>

                </button>

            </div>


            {{-- Reset --}}
            <div class="col-auto">

                <a href="{{ route('items.index') }}"
                   class="btn btn-secondary"
                   title="Reset">

                    <i class="fas fa-undo"></i>

                </a>

            </div>


            {{-- Add Item --}}
            <div class="col-auto">

                <a href="{{ route('items.create') }}"
                   class="btn btn-primary">

                    <i class="fas fa-plus mr-1"></i>

                    Add Item

                </a>

            </div>

        </div>

    </form>

</div>

        </div>


        {{-- Items Card --}}
        <div class="card card-primary card-outline">

            <div class="card-header">

                <h3 class="card-title">

                    <i class="fas fa-boxes mr-2"></i>

                    Item List

                </h3>


                <div class="card-tools">

                    <span class="badge badge-primary">

                        {{ $items->total() }}

                        {{ $items->total() == 1 ? 'Item' : 'Items' }}

                    </span>

                </div>

            </div>


            <div class="card-body p-0">

                <div class="table-responsive">

                    <table class="table table-bordered table-hover mb-0 table-sm table-body-small td">

                        <thead class="table-head-small th">

                            <tr>

                                <th style="width: 60px;">
                                    #
                                </th>

                                <th style="width: 170px;">
                                    Item Code
                                </th>

                                <th>
                                    Item Name
                                </th>

                                <th>
                                    Category
                                </th>

                                <th style="width: 90px;">
                                    Unit
                                </th>

                                <th>
                                    Supplier
                                </th>

                                <th style="width: 90px;">
                                    Status
                                </th>

                                <th style="width: 280px;">
                                    Actions
                                </th>

                            </tr>

                        </thead>


                        <tbody>

                            @forelse($items as $item)
                                <tr>

                                    {{-- Serial --}}
                                    <td>

                                        {{ $items->firstItem() + $loop->index }}

                                    </td>

                                    {{-- Item Code --}}
                                    <td>

                                        <h6 class="text-muted">
                                            {{ $item->item_code }}
                                        </h6>

                                    </td>

                                   {{-- Item Name --}}
                                    <td title="{{ $item->item_name }}">
                                        {{ $item->item_name 
                                            ? \Illuminate\Support\Str::words($item->item_name, 3, '...') 
                                            : '-' }}
                                    </td>

                                    {{-- Category --}}
                                    <td>

                                        {{ $item->category->name ?? '-' }}

                                    </td>


                                    {{-- Unit --}}
                                    <td>

                                        {{ $item->unit->code ?? '-' }}

                                    </td>


                                    {{-- Default Supplier --}}
                                    <td title="{{ $item->defaultSupplier->name ?? '-' }}">
                                        {{ $item->defaultSupplier 
                                            ? \Illuminate\Support\Str::words($item->defaultSupplier->name, 3, '...') 
                                            : '-' }}
                                    </td>


                                    


                                    {{-- Status --}}
                                    <td>

                                        @if ($item->status)
                                            <span class="badge badge-success">

                                                <i class="fas fa-check mr-1"></i>

                                                Active

                                            </span>
                                        @else
                                            <span class="badge badge-secondary">

                                                <i class="fas fa-times mr-1"></i>

                                                Inactive

                                            </span>
                                        @endif

                                    </td>


                                    {{-- Actions --}}
                                    <td>

                                        {{-- ================================================= --}}
                                        {{-- Suppliers --}}
                                        {{-- ================================================= --}}

                                        <a href="{{ route('items.suppliers.index', $item) }}" class="btn btn-sm btn-info"
                                            title="Suppliers Item">

                                            <i class="fas fa-truck"></i>

                                        </a>


                                        {{-- ================================================= --}}
                                        {{-- Usage Logs --}}
                                        {{-- ================================================= --}}

                                        <a href="{{ route('items.usage-logs.index', $item) }}"
                                            class="btn btn-sm btn-secondary" title="Usage Master">

                                            <i class="fas fa-chart-line"></i>

                                        </a>


                                        {{-- ================================================= --}}
                                        {{-- Customer Supply --}}
                                        {{-- ================================================= --}}


                                        {{-- ================================================= --}}
                                        {{-- Purchase Planning --}}
                                        {{-- ================================================= --}}

                                        <a href="{{ route('items.purchase-planning.index', $item) }}"
                                            class="btn btn-sm btn-primary" title="Purchase Planning">

                                            <i class="fas fa-chart-area"></i>

                                        </a>


                                        {{-- ================================================= --}}
                                        {{-- Purchase Settings --}}
                                        {{-- ================================================= --}}

                                        <a href="{{ route('items.purchase-planning.edit', $item) }}"
                                            class="btn btn-sm btn-info" title="Purchase Settings">

                                            <i class="fas fa-cogs"></i>

                                        </a>


                                        {{-- ================================================= --}}
                                        {{-- Edit Item --}}
                                        {{-- ================================================= --}}

                                        <a href="{{ route('items.edit', $item) }}" class="btn btn-sm btn-warning"
                                            title="Edit Item">

                                            <i class="fas fa-edit"></i>

                                        </a>


                                        {{-- ================================================= --}}
                                        {{-- Delete Item --}}
                                        {{-- ================================================= --}}

                                        <form action="{{ route('items.destroy', $item) }}" method="POST" class="d-inline"
                                            onsubmit="return confirm('Are you sure you want to delete this item?');">

                                            @csrf

                                            @method('DELETE')

                                            <button type="submit" class="btn btn-sm btn-danger" title="Delete Item">

                                                <i class="fas fa-trash"></i>

                                            </button>

                                        </form>

                                    </td>



                                </tr>

                            @empty

                                <tr>

                                    <td colspan="8" class="text-center text-muted py-4">

                                        <i class="fas fa-box-open fa-2x mb-2"></i>

                                        <br>

                                        No items found.

                                        <br>

                                        <a href="{{ route('items.create') }}" class="btn btn-sm btn-primary mt-2">

                                            <i class="fas fa-plus mr-1"></i>

                                            Add First Item

                                        </a>

                                    </td>

                                </tr>
                            @endforelse

                        </tbody>

                    </table>

                </div>

            </div>


            {{-- Pagination --}}
            @if ($items->hasPages())
                <div class="card-footer clearfix">

                    <div class="float-right">

                        {{ $items->links('pagination::bootstrap-4') }}

                    </div>

                </div>
            @endif

        </div>

    </div>

@endsection
@push('scripts')

<script>
$(document).ready(function () {

    /*
    |--------------------------------------------------------------------------
    | Product Model Select2 AJAX
    |--------------------------------------------------------------------------
    */

    $('#filter_product_model_id').select2({
        width: '100%',
        placeholder: 'Search Product Model...',
        allowClear: true,
        minimumInputLength: 0,

        ajax: {
            url: "{{ route('items.search.product-models') }}",
            dataType: 'json',
            delay: 250,

            data: function (params) {
                return {
                    q: params.term || ''
                };
            },

            processResults: function (data) {
                return {
                    results: data.results
                };
            },

            cache: true
        }
    });


    /*
    |--------------------------------------------------------------------------
    | Item Code Select2 AJAX
    |--------------------------------------------------------------------------
    */

    $('#filter_item_id').select2({
        width: '100%',
        placeholder: 'Search Item Code...',
        allowClear: true,
        minimumInputLength: 0,

        ajax: {
            url: "{{ route('items.search.item-codes') }}",
            dataType: 'json',
            delay: 250,

            data: function (params) {

                return {
                    q: params.term || '',
                    product_model_id: $('#filter_product_model_id').val()
                };
            },

            processResults: function (data) {
                return {
                    results: data.results
                };
            },

            cache: true
        }
    });


    /*
    |--------------------------------------------------------------------------
    | When Product Model Changes
    |--------------------------------------------------------------------------
    |
    | Clear previous Item Code because it may belong to another model.
    |--------------------------------------------------------------------------
    */

    $('#filter_product_model_id').on('change', function () {

        $('#filter_item_id')
            .val(null)
            .trigger('change');

    });

});
</script>

@endpush
