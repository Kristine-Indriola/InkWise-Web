@extends('layouts.owner.app')

@section('content')
@include('layouts.owner.sidebar')

@include('owner.reports.partials.base', [
    'pageTitle' => 'Inventory Reports',
    'pageSubtitle' => 'Inventory health, replenishment alerts, and exportable summaries',
    'summaryCards' => [
        [
            'label' => 'Total Inventory',
            'chip' => ['text' => 'Items', 'accent' => true],
            'icon' => 'inventory-total',
            'value' => '850',
            'meta' => 'Materials tracked',
        ],
        [
            'label' => 'Low Stock Alerts',
            'chip' => ['text' => 'Alert', 'accent' => true],
            'icon' => 'inventory-low',
            'value' => '12',
            'meta' => 'Needs reorder',
        ],
        [
            'label' => 'Out of Stock',
            'chip' => ['text' => 'Critical', 'accent' => true],
            'icon' => 'inventory-out',
            'value' => '5',
            'meta' => 'Immediate attention required',
        ],
        [
            'label' => 'Pending Deliveries',
            'chip' => ['text' => 'Incoming', 'accent' => true],
            'icon' => 'inventory-pending',
            'value' => '8',
            'meta' => 'Scheduled this week',
        ],
    ],
    'charts' => [
        ['id' => 'inventoryChart', 'title' => 'Inventory Levels'],
    ],
    'tableTitle' => 'Inventory Reports',
    'generateModalTitle' => 'Generate Inventory Report',
])
@endsection
