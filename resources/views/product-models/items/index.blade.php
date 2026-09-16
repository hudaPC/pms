@extends('layouts.admin')

@section('title', 'Product Model Items')

@section('content')

<div class="container py-4">

    {{-- Page Header --}}
    <div class="d-flex justify-content-between align-items-center mb-4">

        <div>

            <h2 class="mb-1">
                Product Model Items
            </h2>

            <p class="text-muted mb-0">
                {{ $productModel->model_code }}
                @if($productModel->document_no)
                    - {{ $productModel->document_no }}
                @endif
            </p>

        </div>

        <div>

            <a href="{{ route('product-models.items.create', $productModel) }}"
               class="btn btn-primary">

                <i class="fas fa-plus mr-1"></i>

                Add Model Item

            </a>

            <a href="{{ route('product-models.index') }}"
               class="btn btn-secondary">

                <i class="fas fa-arrow-left mr-1"></i>

                Back to Product Models

            </a>

        </div>

    </div>


    {{-- Product Model Information --}}
    <div class="card card-primary card-outline mb-4">

        <div class="card-header">

            <h3 class="card-title">

                <i class="fas fa-cubes mr-2"></i>

                Product Model Information

            </h3>

        </div>

        <div class="card-body">

            <div class="row">

                <div class="col-md-3">

                    <strong>Model Code</strong>

                    <div class="mt-1">
                        {{ $productModel->model_code }}
                    </div>

                </div>

                <div class="col-md-3">

                    <strong>Document No.</strong>

                    <div class="mt-1">
                        {{ $productModel->document_no ?? '-' }}
                    </div>

                </div>

                <div class="col-md-2">

                    <strong>Revision</strong>

                    <div class="mt-1">
                        {{ $productModel->revision ?? '-' }}
                    </div>

                </div>

                <div class="col-md-2">

                    <strong>Approval Date</strong>

                    <div class="mt-1">
                        {{ $productModel->approval_date
                            ? $productModel->approval_date->format('d-M-Y')
                            : '-'
                        }}
                    </div>

                </div>

                <div class="col-md-2">

                    <strong>Status</strong>

                    <div class="mt-1">

                        @if($productModel->status)

                            <span class="badge badge-success">
                                Active
                            </span>

                        @else

                            <span class="badge badge-secondary">
                                Inactive
                            </span>

                        @endif

                    </div>

                </div>

            </div>

        </div>

    </div>


    {{-- BOM Table --}}
    <div class="card shadow-sm">

        <div class="card-header">

            <h3 class="card-title">

                <i class="fas fa-list mr-2"></i>

                BOM / Component Items

            </h3>

        </div>


        <div class="card-body">

            <div class="table-responsive">

                <table class="table table-bordered table-hover align-middle table-sm">

                    <thead class="thead-light" thead-small>

                        <tr>

                           

                            <th  width="170">Item Code</th>

                            <th>Item Name</th>

                            <th>Old Part No.</th>

                            <th>Revision</th>

                            <th>Quantity</th>

                          

                            <th>Remarks</th>

                            <th width="130">Actions</th>

                        </tr>

                    </thead>


                    <tbody>

                        @forelse($modelItems as $modelItem)

                            <tr>

                                <td>

                                        {{ $modelItem->item->item_code ?? '-' }}
                                    

                                </td>

                                {{-- <td>
                                    {{ $modelItem->item->item_name ?? '-' }}
                                </td> --}}

                                    <td title="{{ $modelItem->item->item_name}}">
                                        {{ $modelItem->item->item_name 
                                            ? \Illuminate\Support\Str::words($modelItem->item->item_name, 3, '...') 
                                            : '-' }}
                                    </td>







                                <td>
                                    {{ $modelItem->old_part_no ?? '-' }}
                                </td>

                                <td>
                                    {{ $modelItem->revision ?? '-' }}
                                </td>

                                <td>

                                    {{ number_format(
                                        $modelItem->quantity,
                                        3
                                    ) }}

                                    {{ $modelItem->item->unit->name ?? '' }}

                                </td>

                                <td>
                                    {{ $modelItem->remarks ?? '-' }}
                                </td>

                                <td>

                                    <a href="{{ route(
                                        'product-models.items.edit',
                                        [$productModel, $modelItem]
                                    ) }}"
                                       class="btn btn-sm btn-warning">

                                        <i class="fas fa-edit"></i>

                                        <!-- Edit -->

                                    </a>


                                    <form action="{{ route(
                                        'product-models.items.destroy',
                                        [$productModel, $modelItem]
                                    ) }}"
                                          method="POST"
                                          class="d-inline"
                                          onsubmit="return confirm('Are you sure you want to remove this item from the product model?');">

                                        @csrf

                                        @method('DELETE')

                                        <button type="submit"
                                                class="btn btn-sm btn-danger">

                                            <i class="fas fa-trash"></i>

                                            <!-- Delete -->

                                        </button>

                                    </form>

                                </td>

                            </tr>

                        @empty

                            <tr>

                                <td colspan="8"
                                    class="text-center text-muted py-5">

                                    No items have been added to this product model.

                                </td>

                            </tr>

                        @endforelse

                    </tbody>

                </table>

            </div>

        </div>




            {{-- Pagination --}}
            @if ($modelItems->hasPages())
                <div class="card-footer clearfix">

                    <div class="float-right">

                        {{ $modelItems->links('pagination::bootstrap-4') }}

                    </div>

                </div>
            @endif


        

        <!-- @if($modelItems->hasPages())

            <div class="card-footer">

                {{ $modelItems->links() }}

            </div>

        @endif -->

    </div>

</div>

@endsection