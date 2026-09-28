@extends(auth()->user()->role === 'admin' ? 'layouts.admin' : 'layouts.user')

@section('title', 'Quick Edit Items')

@section('content')
<div class="container-fluid">

    <div class="row mb-3 align-items-end">
        <div class="col-md-6">
            <h1 class="m-0">Quick Edit Items</h1>
            <small class="text-muted">Select a Product Model, then edit Item Code, Item Name and Unit Price</small>
        </div>
        <div class="col-md-6 text-right">
            <a href="{{ route('items.index') }}" class="btn btn-secondary">
                <i class="fas fa-arrow-left mr-1"></i> Back to Items
            </a>
        </div>
    </div>

    {{-- Product Model dropdown --}}
    <div class="card card-primary card-outline">
        <div class="card-body">
            <div class="row">
                <div class="col-md-5">
                    <label class="small mb-1">Product Model</label>
                    <select id="quick_product_model_id" class="form-control no-clear">
                        <option value=""></option>
                        @foreach($productModels as $model)
                            <option value="{{ $model->id }}">
                                {{ $model->model_code }}{{ $model->document_no ? ' - ' . $model->document_no : '' }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>
    </div>

    {{-- Items table --}}
    <div class="card card-primary card-outline">
        <div class="card-header">
            <h3 class="card-title"><i class="fas fa-boxes mr-2"></i> Items</h3>
            <div class="card-tools">
                <span class="badge badge-primary" id="itemCount">0 Items</span>
            </div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-bordered table-hover mb-0 table-sm">
                    <thead>
                        <tr>
                            <th style="width:60px;">#</th>
                            <th style="width:200px;">Item Code</th>
                            <th>Item Name</th>
                            <th>Default Supplier</th>
                            <th style="width:140px;">Unit Price</th>
                            <th style="width:90px;">Action</th>
                        </tr>
                    </thead>
                    <tbody id="itemsBody">
                        <tr>
                            <td colspan="6" class="text-center text-muted py-4">
                                Select a Product Model to load its items.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

{{-- Edit Modal --}}
<div class="modal fade" id="quickEditModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <form id="quickEditForm" autocomplete="off">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Item</h5>
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="qe_update_url">

                    <div class="form-group">
                        <label>Item Code <span class="text-danger">*</span></label>
                        <input type="text" id="qe_item_code" class="form-control" maxlength="50">
                        <small class="text-danger" id="err_item_code"></small>
                    </div>

                    <div class="form-group">
                        <label>Item Name <span class="text-danger">*</span></label>
                        <input type="text" id="qe_item_name" class="form-control" maxlength="200">
                        <small class="text-danger" id="err_item_name"></small>
                    </div>

                    <div class="form-group mb-0">
                        <label>Unit Price <small class="text-muted" id="qe_supplier_hint"></small></label>
                        <input type="number" step="0.0001" min="0" id="qe_unit_price" class="form-control">
                        <small class="text-danger" id="err_unit_price"></small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary" id="qe_save">
                        <i class="fas fa-save mr-1"></i> Save
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(function () {

    const listUrl = "{{ route('items.quick-edit.list') }}";
    const csrf    = $('meta[name="csrf-token"]').attr('content');
    let itemsById = {};

    function esc(v) {
        return $('<div>').text(v ?? '').html();
    }

    function rowHtml(item, index) {
        return `
            <tr data-id="${item.id}">
                <td>${index}</td>
                <td class="c-code">${esc(item.item_code)}</td>
                <td class="c-name">${esc(item.item_name)}</td>
                <td class="c-supplier">${item.supplier ? esc(item.supplier) : '<span class="text-muted">-</span>'}</td>
                <td class="c-price">${item.unit_price !== null ? esc(item.unit_price) : '<span class="text-muted">-</span>'}</td>
                <td>
                    <button type="button" class="btn btn-sm btn-warning btn-edit" data-id="${item.id}" title="Edit">
                        <i class="fas fa-edit"></i>
                    </button>
                </td>
            </tr>`;
    }

    function loadItems(modelId) {
        if (!modelId) {
            $('#itemsBody').html('<tr><td colspan="6" class="text-center text-muted py-4">Select a Product Model to load its items.</td></tr>');
            $('#itemCount').text('0 Items');
            return;
        }

        $('#itemsBody').html('<tr><td colspan="6" class="text-center py-4"><i class="fas fa-spinner fa-spin"></i> Loading...</td></tr>');

        $.get(listUrl, { product_model_id: modelId }, function (res) {
            itemsById = {};
            if (!res.items.length) {
                $('#itemsBody').html('<tr><td colspan="6" class="text-center text-muted py-4">No items found for this model.</td></tr>');
                $('#itemCount').text('0 Items');
                return;
            }
            let html = '';
            res.items.forEach((item, i) => {
                itemsById[item.id] = item;
                html += rowHtml(item, i + 1);
            });
            $('#itemsBody').html(html);
            $('#itemCount').text(res.items.length + (res.items.length === 1 ? ' Item' : ' Items'));
        }).fail(function () {
            toastr.error('Could not load items.');
        });
    }

    $('#quick_product_model_id').on('change', function () {
        loadItems($(this).val());
    });

    // Open modal
    $('#itemsBody').on('click', '.btn-edit', function () {
        const item = itemsById[$(this).data('id')];
        $('.text-danger').text('');

        $('#qe_update_url').val(item.update_url);
        $('#qe_item_code').val(item.item_code);
        $('#qe_item_name').val(item.item_name);
        $('#qe_unit_price').val(item.unit_price ?? '');

        if (item.has_supplier) {
            $('#qe_unit_price').prop('disabled', false);
            $('#qe_supplier_hint').text('(supplier: ' + item.supplier + ')');
        } else {
            $('#qe_unit_price').prop('disabled', true);
            $('#qe_supplier_hint').text('(no default supplier set)');
        }

        $('#quickEditModal').modal('show');
    });

    $('#quickEditModal').on('shown.bs.modal', function () {
        $('#qe_item_code').trigger('focus').select();
    });

    // Save
    $('#quickEditForm').on('submit', function (e) {
        e.preventDefault();
        $('.text-danger').text('');

        const url = $('#qe_update_url').val();
        const btn = $('#qe_save').prop('disabled', true);

        const payload = {
            _method: 'PUT',
            _token: csrf,
            item_code: $('#qe_item_code').val(),
            item_name: $('#qe_item_name').val(),
        };
        if (!$('#qe_unit_price').prop('disabled')) {
            payload.unit_price = $('#qe_unit_price').val();
        }

        $.ajax({ url: url, type: 'POST', data: payload })
            .done(function (res) {
                const item = res.item;
                itemsById[item.id] = item;

                const $row = $('#itemsBody tr[data-id="' + item.id + '"]');
                const index = $row.find('td:first').text();
                $row.replaceWith(rowHtml(item, index));

                $('#quickEditModal').modal('hide');
                toastr.success(res.message);
            })
            .fail(function (xhr) {
                if (xhr.status === 422 && xhr.responseJSON.errors) {
                    $.each(xhr.responseJSON.errors, function (field, msgs) {
                        $('#err_' + field).text(msgs[0]);
                    });
                } else {
                    toastr.error('Something went wrong. Please try again.');
                }
            })
            .always(function () {
                btn.prop('disabled', false);
            });
    });
});
</script>
@endpush