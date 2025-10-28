@extends('layouts.owner.app')

@section('content')
@include('layouts.owner.sidebar')

@include('owner.reports.partials.base', [
    'pageTitle' => 'Sales Reports',
    'pageSubtitle' => 'Sales analytics and exportable performance summaries',
    'summaryCards' => [
        [
            'label' => 'Total Sales',
            'chip' => ['text' => 'Revenue', 'accent' => true],
            'value' => '₱120,500',
            'meta' => 'This period',
        ],
        [
            'label' => 'Orders Fulfilled',
            'chip' => ['text' => 'Orders', 'accent' => true],
            'value' => '320',
            'meta' => 'Completed orders',
        ],
        [
            'label' => 'Average Order Value',
            'chip' => ['text' => 'AOV', 'accent' => true],
            'value' => '₱1,550',
            'meta' => 'Per order',
        ],
        [
            'label' => 'Revenue Growth',
            'chip' => ['text' => 'Trend', 'accent' => true],
            'value' => '+12.4%',
            'meta' => 'vs last period',
        ],
    ],
    'charts' => [
        ['id' => 'salesChart', 'title' => 'Sales Overview'],
    ],
    'tableTitle' => 'Sales Reports',
    'generateModalTitle' => 'Generate Sales Report',
])
@endsection
