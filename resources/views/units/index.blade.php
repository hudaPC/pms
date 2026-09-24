@extends(auth()->user()->role === 'admin' ? 'layouts.admin' : 'layouts.user')

@section('title', 'Units')

@section('content')

    <div class="container">

        {{-- Page Header --}}
        <div class="row mb-3">

            <div class="col-md-6">

                <h1 class="m-0">
                    Units
                </h1>

                <small class="text-muted">
                    Manage item measurement units
                </small>

            </div>


            <div class="col-md-6 text-md-right mt-2 mt-md-0">

<a href="{{ auth()->user()->role === 'admin' ? route('units.create') : '#' }}"
                class="btn btn-primary {{ auth()->user()->role !== 'admin' ? 'disabled' : '' }}"
                @if(auth()->user()->role !== 'admin')
                    tabindex="-1"
                    aria-disabled="true"
                    style="pointer-events: none; opacity: 0.5;"
                @endif
                >

                <i class="fas fa-plus mr-1"></i>

                Add Unit

                </a>

            </div>

        </div>


        {{-- Units Card --}}
        <div class="card card-primary card-outline">

            <div class="card-header">

                <h3 class="card-title">

                    <i class="fas fa-ruler-combined mr-2"></i>

                    Unit List

                </h3>


                <div class="card-tools">

                    <span class="badge badge-primary">

                        {{ $units->total() }}

                        {{ $units->total() == 1 ? 'Unit' : 'Units' }}

                    </span>

                </div>

            </div>


            <div class="card-body p-0">

                <div class="table-responsive">

                    <table class="table table-bordered table-hover mb-0 table-striped table-sm">

                        <thead>

                            <tr>

                                <th style="width: 70px;">
                                    #
                                </th>

                                <th style="width: 140px;">
                                    Code
                                </th>

                                <th>
                                    Unit Name
                                </th>

                                <th style="width: 150px;">
                                    Decimal Places
                                </th>

                                <th style="width: 110px;">
                                    Status
                                </th>

                                <th style="width: 150px;">
                                    Actions
                                </th>

                            </tr>

                        </thead>


                        <tbody>

                            @forelse($units as $unit)

                                <tr>

                                    {{-- Serial --}}
                                    <td>

                                        {{ $units->firstItem() + $loop->index }}

                                    </td>


                                    {{-- Code --}}
                                    <td>

                                        <strong>
                                            {{ $unit->code }}
                                        </strong>

                                    </td>


                                    {{-- Unit Name --}}
                                    <td>

                                        {{ $unit->name }}

                                    </td>


                                    {{-- Decimal Places --}}
                                    <td>

                                        {{ $unit->decimal_places }}

                                    </td>


                                    {{-- Status --}}
                                    <td>

                                        @if($unit->status)

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

                                        
                                            <a href="{{ auth()->user()->role === 'admin' ? route('units.edit', $unit) : '#' }}"
                                            class="btn btn-sm btn-warning {{ auth()->user()->role !== 'admin' ? 'disabled' : '' }}"
                                            title="Edit Unit"
                                            @if(auth()->user()->role !== 'admin')
                                                tabindex="-1"
                                                aria-disabled="true"
                                                style="pointer-events: none; opacity: 0.5;"
                                            @endif
                                        >

                                            <i class="fas fa-edit"></i>

                                        </a>


                                        <form
                                            action="{{ route('units.destroy', $unit) }}"
                                            method="POST"
                                            class="d-inline"
                                            onsubmit="return confirm('Are you sure you want to delete this unit?');"
                                        >

                                            @csrf

                                            @method('DELETE')

                                            <button
                                                type="submit"
                                                class="btn btn-sm btn-danger"
                                                title="Delete Unit"
                                                {{ auth()->user()->role !== 'admin' ? 'disabled' : '' }}
                                            >

                                                <i class="fas fa-trash"></i>

                                            </button>

                                        </form>

                                    </td>

                                </tr>

                            @empty

                                <tr>

                                    <td colspan="6" class="text-center text-muted py-4">

                                        <i class="fas fa-ruler-combined fa-2x mb-2"></i>

                                        <br>

                                        No units found.

                                        <br>

                                        @if(auth()->user()->role === 'admin')
    
                                                <a href="{{ route('units.create') }}"
                                                class="btn btn-sm btn-primary mt-2">

                                                <i class="fas fa-plus mr-1"></i>

                                                Add First Unit

                                            </a>
                                        @endif

                                    </td>

                                </tr>

                            @endforelse

                        </tbody>

                    </table>

                </div>

            </div>


            {{-- Pagination --}}
            @if($units->hasPages())

                <div class="card-footer clearfix">

                    <div class="float-right">

                        {{ $units->links('pagination::bootstrap-4') }}

                    </div>

                </div>

            @endif

        </div>

    </div>

@endsection