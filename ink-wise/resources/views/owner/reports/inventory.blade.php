@extends('layouts.owner.app')

@section('content')
@include('layouts.owner.sidebar')

@include('owner.reports.partials.base', [
    'pageTitle' => 'Inventory Reports',
    'pageSubtitle' => $pageSubtitle ?? 'Inventory health, replenishment alerts, and exportable summaries',
    'summaryCards' => $summaryCards ?? [],
    'charts' => $charts ?? [],
    'tableTitle' => $tableTitle ?? 'Inventory Snapshot',
    'tableSubtitle' => $tableSubtitle ?? null,
    'tableConfig' => $tableConfig ?? [],
    'generateModalTitle' => 'Generate Inventory Report',
    'activeRange' => $activeRange ?? 'all',
    'rangeReload' => $rangeReload ?? false,
    'showGenerateControls' => $showGenerateControls ?? false,
])
@endsection
