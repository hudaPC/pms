@extends('layouts.admin')

@section('title', 'Add Product Model Item')

@section('content')

    <div class="container py-4">

        {{-- Page Header --}}
        <div class="d-flex justify-content-between align-items-center mb-4">

            <div>

                <h2 class="mb-1">
                    Add Model Item
                </h2>

                <p class="text-muted mb-0">
                    {{ $productModel->model_code }}
                    @if ($productModel->document_no)
                        - {{ $productModel->document_no }}
                    @endif
                </p>

            </div>

            <a href="{{ route('product-models.items.index', $productModel) }}" class="btn btn-secondary">

                <i class="fas fa-arrow-left mr-1"></i>

                Back to Model Items

            </a>

        </div>


        {{-- Validation Errors --}}
        @if ($errors->any())

            <div class="alert alert-danger">

                <strong>Please correct the following errors:</strong>

                <ul class="mb-0 mt-2">

                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach

                </ul>

            </div>

        @endif


        {{-- Product Model Information --}}
        <div class="card card-primary card-outline mb-4">

            <div class="card-header">

                <h3 class="card-title">

                    <i class="fas fa-cubes mr-2"></i>

                    Product Model

                </h3>

            </div>

            <div class="card-body">

                <div class="row">

                    <div class="col-md-4">

                        <strong>Model Code</strong>

                        <div class="mt-1">
                            {{ $productModel->model_code }}
                        </div>

                    </div>

                    <div class="col-md-4">

                        <strong>Document No.</strong>

                        <div class="mt-1">
                            {{ $productModel->document_no ?? '-' }}
                        </div>

                    </div>

                    <div class="col-md-4">

                        <strong>Revision</strong>

                        <div class="mt-1">
                            {{ $productModel->revision ?? '-' }}
                        </div>

                    </div>

                </div>

            </div>

        </div>


        {{-- Model Item Form --}}
        <div class="card shadow-sm">

            <div class="card-header">

                <h3 class="card-title">

                    <i class="fas fa-plus-circle mr-2"></i>

                    Component Item Information

                </h3>

            </div>


            <form action="{{ route('product-models.items.store', $productModel) }}" method="POST">

                @csrf


                <div class="card-body">

                    <div class="row">

                        {{-- Item --}}
                        <div class="col-md-9 mb-3">

                            <label for="item_id">

                                Item

                                <span class="text-danger">*</span>

                            </label>

                            <select name="item_id" id="item_id"
                                class="form-control no-clear @error('item_id') is-invalid @enderror" required>

                                <option value="">
                                    -- Select --
                                </option>

                                @foreach ($items as $item)
                                    @php
                                        $usage = $lastUsage->get($item->id);
                                    @endphp

                                    <option value="{{ $item->id }}" data-old-part-no="{{ $usage->old_part_no ?? '' }}"
                                        data-revision="{{ $usage->revision ?? '' }}"
                                        {{ old('item_id', request('item_id')) == $item->id ? 'selected' : '' }}>

                                        {{ $item->item_code }}
                                        -
                                        {{ $item->item_name }}

                                    </option>
                                @endforeach

                            </select>

                            @error('item_id')
                                <div class="invalid-feedback">
                                    {{ $message }}
                                </div>
                            @enderror

                            <small id="duplicateWarning" class="form-text text-danger" style="display: none;">
                                <i class="fas fa-exclamation-triangle mr-1"></i>
                                This item is already added to this product model.
                            </small>

                        </div>


                        {{-- Old Part No --}}
                        <div class="col-md-4 mb-3">

                            <label for="old_part_no">
                                Old Part No. / Model No.
                            </label>

                            <input type="text" name="old_part_no" id="old_part_no" value="{{ old('old_part_no') }}"
                                class="form-control @error('old_part_no') is-invalid @enderror" maxlength="100">

                            @error('old_part_no')
                                <div class="invalid-feedback">
                                    {{ $message }}
                                </div>
                            @enderror

                            <small class="form-text text-muted">
                                Auto-filled from last usage. You can edit it.
                            </small>

                        </div>


                        {{-- Revision --}}
                        <div class="col-md-2 mb-3">

                            <label for="revision">
                                Revision
                            </label>

                            <input type="text" name="revision" id="revision" value="{{ old('revision') }}"
                                class="form-control @error('revision') is-invalid @enderror" maxlength="50">

                            @error('revision')
                                <div class="invalid-feedback">
                                    {{ $message }}
                                </div>
                            @enderror

                        </div>


                        {{-- Quantity --}}
                        <div class="col-md-3 mb-3">

                            <label for="quantity">

                                Quantity

                                <span class="text-danger">*</span>

                            </label>

                            <input type="number" name="quantity" id="quantity" value="{{ old('quantity', 1) }}"
                                class="form-control @error('quantity') is-invalid @enderror" step="0.001" required>

                            @error('quantity')
                                <div class="invalid-feedback">
                                    {{ $message }}
                                </div>
                            @enderror

                        </div>


                        {{-- Remarks --}}
                        <div class="col-md-12 mb-3">

                            <label for="remarks">
                                Remarks
                            </label>

                            <textarea name="remarks" id="remarks" rows="3" class="form-control @error('remarks') is-invalid @enderror"
                                placeholder="Optional remarks">{{ old('remarks') }}</textarea>

                            @error('remarks')
                                <div class="invalid-feedback">
                                    {{ $message }}
                                </div>
                            @enderror

                        </div>

                    </div>

                </div>

                <div class="mb-3 ml-3">
                    <button type="submit" class="btn btn-primary">

                        <i class="fas fa-save mr-1"></i>

                        Save Model Item

                    </button>

                    <a href="{{ route('product-models.items.index', $productModel) }}" class="btn btn-secondary ml-2">

                        Cancel

                    </a>
                </div>

                <div class="card-footer">

                    @if ($existingItems->isNotEmpty())

                        <div class="mb-3">

                            <small class="text-muted d-block mb-1">
                                <strong>Already added to this model ({{ $existingItems->count() }}):</strong>
                            </small>

                            <small class="text-muted">

                                @foreach ($existingItems as $modelItem)
                                    {{ $modelItem->item->item_code ?? 'N/A' }}
                                    -
                                    {{ $modelItem->item->item_name ?? 'Unknown Item' }}
                                    (Qty: {{ number_format($modelItem->quantity, 0) }})
                                    {{ !$loop->last ? '|| ' : '' }}
                                @endforeach

                            </small>

                        </div>
                    @else
                        <small class="text-muted d-block mb-3">
                            No items added yet.
                        </small>

                    @endif



                </div>

            </form>

        </div>

    </div>

@endsection

@push('scripts')
    <script>
        $(document).ready(function() {

            const itemSelect = document.getElementById('item_id');
            const oldPartNoInput = document.getElementById('old_part_no');
            const revisionInput = document.getElementById('revision');

            function loadItemInformation() {

                const option = itemSelect.options[itemSelect.selectedIndex];

                if (!option || !option.value) {
                    return;
                }

                const oldPartNo = option.dataset.oldPartNo || '';
                const revision = option.dataset.revision || '';

                /*
                |--------------------------------------------------------------------------
                | Auto Fill Old Part No.
                |--------------------------------------------------------------------------
                */

                if (!oldPartNoInput.value && oldPartNo) {
                    oldPartNoInput.value = oldPartNo;
                }

                /*
                |--------------------------------------------------------------------------
                | Auto Fill Revision
                |--------------------------------------------------------------------------
                */

                if (!revisionInput.value && revision) {
                    revisionInput.value = revision;
                }
            }

            $(itemSelect).on('change', loadItemInformation);

            if (itemSelect.value) {
                loadItemInformation();
            }

        });
    </script>
@endpush
