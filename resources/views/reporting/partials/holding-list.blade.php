@php
    $showTools = $showTools ?? true;

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

    // Unique prefix so ids stay unique if the partial is used twice on a page
    $uid = $uid ?? 'ht';
@endphp

<style>
    .ht-grn { border: 0; border-radius: .6rem; box-shadow: 0 2px 10px rgba(0, 0, 0, .08); overflow: hidden; }
    .ht-grn-head { background: #f4f6fb; cursor: pointer; }
    .ht-model-head { background: #fafbfd; border-top: 1px solid #e9ecef; cursor: pointer; }
    .ht-bar { height: 6px; border-radius: 3px; background: #e9ecef; overflow: hidden; min-width: 90px; }
    .ht-bar > span { display: block; height: 100%; }
    .ht-toggle .fa-minus { display: none; }
    .ht-toggle:not(.collapsed) .fa-plus { display: none; }
    .ht-toggle:not(.collapsed) .fa-minus { display: inline; }
    .ht-pill { font-size: .85rem; padding: .35em .7em; border-radius: 50rem; }
    .badge-orange { background: #fd7e14; color: #fff; }
</style>

{{-- Legend (+ expand/collapse buttons on the report page) --}}
<div class="d-flex flex-wrap justify-content-between align-items-center mb-2">
    <div class="small">
        <span class="badge badge-success ht-pill">&lt; 30 days</span>
        <span class="badge badge-warning ht-pill">30 – 89</span>
        <span class="badge badge-orange ht-pill">90 – 179</span>
        <span class="badge badge-danger ht-pill">180+ days</span>
    </div>
    @if ($showTools)
        <div>
            <button type="button" class="btn btn-sm btn-outline-primary" id="htExpandAll">
                <i class="fas fa-plus-square mr-1"></i> Expand All
            </button>
            <button type="button" class="btn btn-sm btn-outline-secondary" id="htCollapseAll">
                <i class="fas fa-minus-square mr-1"></i> Collapse All
            </button>
        </div>
    @endif
</div>

{{-- ================= GRN LEVEL ================= --}}
@forelse ($groups as $g)
    @php
        $grn = $g['grn'];
        [$gClass, $gColor] = $tone((int) $g['max_days']);
        $grnKey = $uid . '-grn-' . $grn->id;
    @endphp

    <div class="card ht-grn mb-3">

        <div class="ht-grn-head p-3" data-toggle="collapse" data-target="#{{ $grnKey }}" aria-expanded="false">
            <div class="d-flex flex-wrap align-items-center">

                <button type="button" class="btn btn-sm btn-primary ht-toggle collapsed mr-3">
                    <i class="fas fa-plus"></i><i class="fas fa-minus"></i>
                </button>

                <div class="mr-4">
                    <div class="font-weight-bold" style="font-size:1.05rem">
                        <i class="fas fa-truck-loading text-primary mr-1"></i>{{ $grn->grn_number }}
                    </div>
                    <div class="small text-muted">
                        Received {{ $g['receipt_date']->format('d-M-Y') }}
                        @foreach ($grn->purchaseOrders as $po)
                            <span class="badge badge-light border">{{ $po->po_number }}</span>
                        @endforeach
                    </div>
                </div>

                <div class="ml-auto d-flex align-items-center">
                    <span class="badge badge-secondary ht-pill mr-2">{{ $g['item_count'] }} items</span>
                    @if ($g['unused'] > 0)
                        <span class="badge badge-danger ht-pill mr-2">{{ $g['unused'] }} not used</span>
                    @endif
                    <span class="badge badge-{{ $gClass }} ht-pill" title="Longest holding in this GRN">
                        <i class="fas fa-hourglass-half mr-1"></i>
                        up to {{ number_format($g['max_days']) }} days
                    </span>
                </div>

            </div>
        </div>

        <div class="collapse" id="{{ $grnKey }}">

            {{-- ================= MODEL LEVEL ================= --}}
            @foreach ($g['models'] as $m)
                @php
                    [$mClass, $mColor] = $tone((int) $m['max_days']);
                    $modelKey = $grnKey . '-m' . $loop->index;
                @endphp

                <div class="ht-model-head px-3 py-2" data-toggle="collapse" data-target="#{{ $modelKey }}"
                    aria-expanded="false">
                    <div class="d-flex flex-wrap align-items-center pl-4">

                        <button type="button" class="btn btn-xs btn-outline-primary ht-toggle collapsed mr-3"
                            style="padding:.1rem .4rem">
                            <i class="fas fa-plus"></i><i class="fas fa-minus"></i>
                        </button>

                        <div>
                            <i class="fas fa-cubes text-info mr-1"></i>
                            <strong>{{ $m['model'] }}</strong>
                        </div>

                        <div class="ml-auto">
                            <span class="badge badge-secondary mr-1">{{ $m['items']->count() }} items</span>
                            @if ($m['unused'] > 0)
                                <span class="badge badge-danger mr-1">{{ $m['unused'] }} not used</span>
                            @endif
                            <span class="badge badge-{{ $mClass }}">
                                up to {{ number_format($m['max_days']) }} days
                            </span>
                        </div>

                    </div>
                </div>

                {{-- ================= ITEM LEVEL ================= --}}
                <div class="collapse" id="{{ $modelKey }}">
                    <div class="table-responsive px-3 pb-3 pt-2">
                        <table class="table table-sm table-hover mb-0">
                            <thead class="thead-light">
                                <tr>
                                    <th>Item Code</th>
                                    <th>Item Name</th>
                                    <th class="text-right">Received</th>
                                    <th class="text-right">Used</th>
                                    <th class="text-right">Remaining</th>
                                    <th>Status</th>
                                    <th>Last Used</th>
                                    <th>Holding Time</th>
                                    <th style="width:110px"></th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($m['items'] as $row)
                                    @php
                                        [$iClass, $iColor] = $tone((int) $row['days']);
                                        [$sLabel, $sClass] = $statusMap[$row['status']];
                                        $pct = min(100, ($row['days'] / 180) * 100);
                                    @endphp
                                    <tr>
                                        <td><strong>{{ $row['item_code'] }}</strong></td>
                                        <td>{{ $row['item_name'] }}</td>
                                        <td class="text-right">{{ number_format($row['received'], 3) }}</td>
                                        <td class="text-right text-danger">{{ number_format($row['used'], 3) }}</td>
                                        <td class="text-right text-success">
                                            <strong>{{ number_format($row['remaining'], 3) }}</strong>
                                        </td>
                                        <td><span class="badge badge-{{ $sClass }}">{{ $sLabel }}</span></td>
                                        <td>{{ $row['last_usage'] ? $row['last_usage']->format('d-M-Y') : '-' }}</td>
                                        <td>
                                            <span class="badge badge-{{ $iClass }} ht-pill"
                                                title="{{ number_format($row['days']) }} days total">
                                                <i class="fas fa-clock mr-1"></i>{{ $row['label'] }}
                                            </span>
                                        </td>
                                        <td>
                                            <div class="ht-bar" title="{{ number_format($row['days']) }} days">
                                                <span style="width:{{ $pct }}%; background:{{ $iColor }}"></span>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endforeach

        </div>
    </div>

@empty
    <div class="card ht-grn">
        <div class="card-body text-center text-muted py-5">
            <i class="fas fa-inbox fa-2x mb-2"></i>
            <div>No items match this filter.</div>
        </div>
    </div>
@endforelse