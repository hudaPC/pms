@extends('layouts.admin')

@section('title', 'Suppliers')

@section('content')

    <div class="container-fluid">

        {{-- Page Header --}}
        <div class="d-flex justify-content-between align-items-center mb-4">

            <div>

                <h2 class="mb-1">
                    Suppliers
                </h2>

                <p class="text-muted mb-0">
                    Manage suppliers and vendor information
                </p>

            </div>

            <a href="{{ route('suppliers.create') }}" class="btn btn-primary">

                <i class="fas fa-plus mr-1"></i>

                Add Supplier

            </a>

        </div>


        {{-- Supplier Table --}}
        <div class="card shadow-sm">

            <div class="card-body">

                <div class="table-responsive">

                    <table class="table table-bordered table-hover align-middle mb-0 table-striped table-sm">

                        <thead class="table-light">

                            <tr>

                                <th width="60">
                                    #
                                </th>

                                <th width="120">
                                    Code
                                </th>

                                <th>
                                    Name
                                </th>

                                <th>
                                    Contact
                                </th>

                                <th>
                                    Phone
                                </th>

                                <th>
                                    Email
                                </th>

                                <th width="130">
                                    Lead Time
                                </th>

                                <th width="100">
                                    Status
                                </th>

                                <th width="100">
                                    Actions
                                </th>

                            </tr>

                        </thead>


                        <tbody>

                            @forelse($suppliers as $supplier)

                                <tr>

                                    <td>
                                        {{ $suppliers->firstItem() + $loop->index }}
                                    </td>


                                    <td>

                                        <strong>
                                            {{ $supplier->code }}
                                        </strong>

                                    </td>


                                    <td>
                                        {{ $supplier->name }}
                                    </td>


                                    <td>
                                        {{ $supplier->contact_person ?: '-' }}
                                    </td>


                                    <td>
                                        {{ $supplier->phone ?: '-' }}
                                    </td>


                                    <td>
                                        {{ $supplier->email ?: '-' }}
                                    </td>


                                    <td>

                                        @if($supplier->default_lead_time_days !== null)

                                            {{ $supplier->default_lead_time_days }}
                                            day{{ $supplier->default_lead_time_days == 1 ? '' : 's' }}

                                        @else

                                            -

                                        @endif

                                    </td>


                                    <td>

                                        @if($supplier->status)

                                            <span class="badge bg-success">
                                                Active
                                            </span>

                                        @else

                                            <span class="badge bg-secondary">
                                                Inactive
                                            </span>

                                        @endif

                                    </td>


                                    <td>

                                        <a href="{{ route('suppliers.edit', $supplier) }}" class="btn btn-sm btn-warning">

                                            <i class="fas fa-edit mr-1"></i>

                                        </a>


                                        <form action="{{ route('suppliers.destroy', $supplier) }}" method="POST"
                                            class="d-inline"
                                            onsubmit="return confirm('Are you sure you want to delete this supplier?');">

                                            @csrf

                                            @method('DELETE')

                                            <button type="submit" class="btn btn-sm btn-danger">
                                                <i class="fas fa-trash mr-1"></i>                                          

                                            </button>
                                        </form>
                                    </td>
                                </tr>

                            @empty

                                <tr>

                                    <td colspan="9" class="text-center text-muted py-4">

                                        <i class="fas fa-truck mb-2" style="font-size: 28px;">
                                        </i>

                                        <div>
                                            No suppliers found.
                                        </div>

                                    </td>

                                </tr>

                            @endforelse

                        </tbody>

                    </table>

                </div>

            </div>


            {{-- Pagination --}}
            @if($suppliers->hasPages())

                <div class="card-footer">

                    {{ $suppliers->links() }}

                </div>

            @endif

        </div>

    </div>

@endsection