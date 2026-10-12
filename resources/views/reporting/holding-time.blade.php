@extends('layouts.admin')

@section('title', 'Holding Time')

@section('content')

    @php
        // Colour by holding days
        $tone = function (int $d) {
            if ($d >= 180) return ['danger', '#dc3545'];
            if ($d >= 90) return ['orange', '#fd7e14'];
            if ($d >= 30) return ['warning', '#ffc107'];
            return ['success', '#28a745'];
        };

        $statusMap = [
            'unused'  => ['Not Used', 'danger'],
            'partial' => ['Partly Used', 'warning'],
            'used'    => ['Fully Used', 'success'],
        ];
    @endphp

    <style>
        .ht-hero {
            background: linear-gradient(135deg, #1e3c72 0%, #2a5298 60%, #4a7bd0 100%);
            color: #fff;
            border-radius: .6rem;
            padding: 1.25rem 1.5rem;
            box-shadow: 0 6px 18px rgba(30, 60, 114, .25);
        }
        .ht-hero .form-control { border: 0; }
        .ht-stat {
            border-radius: .6rem;
            border: 0;
            border-left: 5px solid;
            box-shadow: 0 2px 10px rgba(0, 0, 0, .08);
        }
        .ht-stat .num { font-size: 1.8rem; font-weight: 700; line-height: 1; }
        .ht-orange { background: #fd7e14; color: #fff; }
        .badge-orange { background: #fd7e14; color: #fff; }

        .ht-grn {
            border: 0;
            border-radius: .6rem;
            box-shadow: 0 2px 10px rgba(0, 0, 0, .08);
            overflow: hidden;
        }
        .ht-grn-head { background: #f4f6fb; cursor: pointer; }
        .ht-model-head { background: #fafbfd; border-top: 1px solid #e9ecef; cursor: pointer; }
        .ht-bar { height: 6px; border-radius: 3px; background: #e9ecef; overflow: hidden; min-width: 90px; }
        .ht-bar > span { display: block; height: 100%; }

        .ht-toggle .fa-minus { display: none; }
        .ht-toggle:not(.collapsed) .fa-plus { display: none; }
        .ht-toggle:not(.collapsed) .fa-minus { display: inline; }
        .ht-pill { font-size: .85rem; padding: .35em .7em; border-radius: 50rem; }
    </style>

    <div class="container-fluid py-3">

        {{-- ================= HERO + FILTER ================= --}}
        <div class="ht-hero mb-4">
            <div class="d-flex flex-wrap justify-content-between align-items-center">

                <div class="mb-2 mb-md-0">
                    <h3 class="mb-1">
                        <i class="fas fa-hourglass-half mr-2"></i> Holding Time Report
                    </h3>
                    <div style="opacity:.85">
                        How long each item has been held since its Goods Receipt, <br> grouped by GRN and Product Model.
                    </div>
                </div>

                                <form method="GET" action="{{ route('reporting.holding-time') }}"
                    class="ht-filter d-flex flex-nowrap align-items-center">

                    <div class="input-group mr-2" style="width:170px">
                        <div class="input-group-prepend">
                            <span class="input-group-text bg-white"><i class="fas fa-calendar-day"></i></span>
                        </div>
                        <input type="number" min="0" name="days" value="{{ $minDays }}"
                            class="form-control" placeholder="Held ≥ days">
                    </div>

                    <div class="mr-2" style="width:170px">
                        <select name="status" class="form-control">
                            <option value="">All items</option>
                            <option value="unused" @selected($status === 'unused')>Not used</option>
                            <option value="partial" @selected($status === 'partial')>Partly used</option>
                            <option value="used" @selected($status === 'used')>Fully used</option>
                        </select>
                    </div>

                    <button class="btn btn-light mr-2 text-nowrap" type="submit">
                        <i class="fas fa-filter mr-1"></i> Filter
                    </button>
                    <a href="{{ route('reporting.holding-time') }}" class="btn btn-outline-light text-nowrap">
                        <i class="fas fa-undo mr-1"></i> Reset
                    </a>
                </form>

            </div>
        </div>

        {{-- ================= SUMMARY CARDS ================= --}}
        <div class="row mb-3">
            <div class="col-md-3 mb-2">
                <div class="card ht-stat" style="border-left-color:#2a5298">
                    <div class="card-body py-3">
                        <div class="text-muted small">Goods Receipts</div>
                        <div class="num text-primary">{{ number_format($summary['grns']) }}</div>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-2">
                <div class="card ht-stat" style="border-left-color:#17a2b8">
                    <div class="card-body py-3">
                        <div class="text-muted small">Items Listed</div>
                        <div class="num text-info">{{ number_format($summary['items']) }}</div>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-2">
                <div class="card ht-stat" style="border-left-color:#dc3545">
                    <div class="card-body py-3">
                        <div class="text-muted small">Not Used Items</div>
                        <div class="num text-danger">{{ number_format($summary['unused']) }}</div>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-2">
                <div class="card ht-stat" style="border-left-color:#fd7e14">
                    <div class="card-body py-3">
                        <div class="text-muted small">Longest Holding</div>
                        <div class="num" style="color:#fd7e14">{{ number_format($summary['oldest']) }}
                            <small class="text-muted" style="font-size:.9rem">days</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Legend + expand/collapse --}}
                @include('reporting.partials.holding-list', ['groups' => $groups, 'showTools' => true])
    </div>

    @push('scripts')
        <script>
            $(function() {
                $('#htExpandAll').on('click', function() {
                    $('.collapse').collapse('show');
                });
                $('#htCollapseAll').on('click', function() {
                    $('.collapse').collapse('hide');
                });
            });
        </script>
    @endpush

@endsection