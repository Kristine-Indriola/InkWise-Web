@extends('layouts.owner.app')

@section('content')
@include('layouts.owner.sidebar')

@include('owner.reports.partials.base', [
    'pageTitle' => 'Sales Reports',
    'pageSubtitle' => 'Sales analytics and exportable performance summaries',
    'summaryCards' => $summaryCards ?? [],
    'charts' => $charts ?? [],
    'tableTitle' => 'Recent sales',
    'tableSubtitle' => 'Line items captured from recent orders.',
    'tableConfig' => $tableConfig ?? [],
    'generateModalTitle' => 'Generate Sales Report',
    'activeRange' => $activeRange ?? 'monthly',
    'rangeReload' => true,
    'showGenerateControls' => false,
])
@endsection
