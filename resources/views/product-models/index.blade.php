@extends('layouts.admin')

@section('title', 'Product Models')

@section('content')

<div class="container py-4">

    <div class="d-flex justify-content-between align-items-center mb-4">

        <div>

            <h2 class="mb-1">
                Product Models
            </h2>

            <p class="text-muted mb-0">
                Manage product models and assembly/BOM references
            </p>

        </div>

        <a href="{{ route('product-models.create') }}"
           class="btn btn-primary">

            <i class="fas fa-plus mr-1"></i>

            Add Product Model

        </a>

    </div>


    <div class="card shadow-sm">

        <div class="card-header">

            <h3 class="card-title">

                <i class="fas fa-cubes mr-2"></i>

                Product Model List

            </h3>

        </div>


        <div class="card-body">

            <div class="table-responsive">

                <table class="table table-bordered table-hover">

                    <thead class="thead-light">

                        <tr>

                            <th>#</th>

                            <th>Model</th>

                            <th>Document No.</th>

                            <th>Revision</th>

                            <th>Approval Date</th>

                            <th>Status</th>

                            <th width="230">
                                Actions
                            </th>

                        </tr>

                    </thead>


                    <tbody>

                        @forelse($productModels as $productModel)

                            <tr>

                                <td>
                                    {{ $productModels->firstItem() + $loop->index }}
                                </td>

                                <td>

                                    <strong>
                                        {{ $productModel->model_code }}
                                    </strong>

                                </td>

                                <td>
                                    {{ $productModel->document_no ?? '-' }}
                                </td>

                                <td>
                                    {{ $productModel->revision ?? '-' }}
                                </td>

                                <td>

                                    {{ $productModel->approval_date
                                        ? $productModel->approval_date->format('d-M-Y')
                                        : '-'
                                    }}

                                </td>

                                <td>

                                    @if($productModel->status)

                                        <span class="badge badge-success">
                                            Active
                                        </span>

                                    @else

                                        <span class="badge badge-secondary">
                                            Inactive
                                        </span>

                                    @endif

                                </td>

                                <td>
                                    <a href="{{ route('product-models.items.index', $productModel) }}"
                                        class="btn btn-sm btn-info">

                                            <i class="fas fa-list"></i>
                                            Model Items

                                        </a>

                                    <a href="{{ route('product-models.edit', $productModel) }}"
                                       class="btn btn-sm btn-warning">

                                        <i class="fas fa-edit"></i>

                                        {{-- Edit --}}

                                    </a>


                                    <form action="{{ route('product-models.destroy', $productModel) }}"
                                          method="POST"
                                          class="d-inline"
                                          onsubmit="return confirm('Are you sure you want to delete this product model?');">

                                        @csrf

                                        @method('DELETE')

                                        <button type="submit"
                                                class="btn btn-sm btn-danger">

                                            <i class="fas fa-trash"></i>

                                            {{-- Delete --}}

                                        </button>

                                    </form>

                                </td>

                            </tr>

                        @empty

                            <tr>

                                <td colspan="7"
                                    class="text-center text-muted py-5">

                                    No product models found.

                                </td>

                            </tr>

                        @endforelse

                    </tbody>

                </table>

            </div>

        </div>


        @if($productModels->hasPages())

            <div class="card-footer">

                {{ $productModels->links() }}

            </div>

        @endif

    </div>

</div>

@endsection