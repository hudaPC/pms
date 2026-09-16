@extends('layouts.admin')

@section('title', 'Stock Adjustments')

@section('content')

    <div class="container py-4">

        <div class="d-flex justify-content-between align-items-center mb-4">

            <div>

                <h2 class="mb-1">
                    Stock Adjustments
                </h2>

                <p class="text-muted mb-0">
                    Manual inventory corrections and adjustments
                </p>

            </div>

            <a href="{{ route('stock-adjustments.create') }}" class="btn btn-primary">

                <i class="fas fa-plus mr-1"></i>

                New Adjustment

            </a>

        </div>


        <div class="card shadow-sm">

            <div class="card-header">

                <h3 class="card-title">

                    <i class="fas fa-balance-scale mr-2"></i>

                    Adjustment History

                </h3>

            </div>


            <div class="card-body">

                <div class="table-responsive">

                    <table class="table table-bordered table-hover table-sm">

                        <thead class="thead-light">

                            <tr>

                                <th>#</th>

                                <th>Date</th>

                                <th>Item Code</th>

                                <th>Item Name</th>

                                <th>Direction</th>

                                <th class="text-right">
                                    Quantity
                                </th>

                                <th>Reference</th>

                                <th>Reason</th>

                                <th>Remarks</th>
                                <th width="120">
                                    Actions
                                </th>

                            </tr>

                        </thead>


                        <tbody>

                            @forelse($adjustments as $adjustment)
                                <tr>

                                    <td>
                                        {{ $adjustments->firstItem() + $loop->index }}
                                    </td>


                                    <td>

                                        {{ $adjustment->adjustment_date ? $adjustment->adjustment_date->format('d-M-Y') : '-' }}

                                    </td>


                                    <td>

                                        <strong>
                                            {{ $adjustment->item->item_code ?? '-' }}
                                        </strong>

                                    </td>


                                    <td>

                                        {{ $adjustment->item->item_name ?? '-' }}

                                    </td>


                                    <td>

                                        @if ($adjustment->direction === 'in')
                                            <span class="badge badge-success">
                                                IN
                                            </span>
                                        @else
                                            <span class="badge badge-danger">
                                                OUT
                                            </span>
                                        @endif

                                    </td>


                                    <td class="text-right">

                                        {{ number_format($adjustment->quantity, 0) }}

                                        <small class="text-muted">

                                            {{ $adjustment->item->unit->name ?? '' }}

                                        </small>

                                    </td>


                                    <td>

                                        {{ $adjustment->reference ?? '-' }}

                                    </td>


                                    <td>

                                        {{ $adjustment->reason ?? '-' }}

                                    </td>


                                    <td>

                                        {{ $adjustment->remarks ?? '-' }}

                                    </td>
                                    <td>

                                        <a href="{{ route('stock-adjustments.edit', $adjustment) }}"
                                            class="btn btn-sm btn-warning">

                                            <i class="fas fa-edit"></i>

                                            {{-- Edit --}}

                                        </a>
                                        <form
                                            action="{{ route('stock-adjustments.destroy', $adjustment) }}"
                                            method="POST" class="d-inline"
                                            onsubmit="return confirm( 'Are you sure you want to delete this stock adjustment?' );">

                                            @csrf
                                            @method('DELETE')

                                            <button type="submit" class="btn btn-sm btn-danger">

                                                <i class="fas fa-trash"></i>

                                                {{-- Delete --}}

                                            </button>

                                        </form>

                                    </td>

                                </tr>


                            @empty

                                <tr>

                                    <td colspan="10" class="text-center text-muted py-5">

                                        No stock adjustments found.

                                    </td>

                                </tr>
                            @endforelse

                        </tbody>

                    </table>

                </div>

            </div>


            @if ($adjustments->hasPages())
                <div class="card-footer">

                    {{ $adjustments->links() }}

                </div>
            @endif

        </div>

    </div>

@endsection
