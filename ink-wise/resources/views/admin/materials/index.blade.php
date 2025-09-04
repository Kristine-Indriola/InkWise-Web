@extends('layouts.admin')

@section('title', 'Materials Management')

@section('content')
<div class="materials-container">
    <link rel="stylesheet" href="{{ asset('css/admin-css/materials.css') }}">
    <h1>Materials Management</h1>

    {{-- Add Material Button --}}
    <a href="{{ route('admin.materials.create') }}" class="btn btn-primary">➕ Add New Material</a>

    {{-- Success Message --}}
    @if(session('success'))
        <div class="alert alert-success">
            {{ session('success') }}
        </div>
    @endif

    {{-- Materials Table --}}
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Material Name</th>
                    <th>Type</th>
                    <th>Unit</th>
                    <th>Unit Cost</th>
                    <th>Stock Level</th>
                    <th>Reorder Level</th>
                    <th>Remarks</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($materials as $material)
                    <tr>
                        <td>{{ $material->material_id }}</td>
                        <td>{{ $material->material_name }}</td>
                        <td>
                            <span class="badge badge-type {{ strtolower($material->material_type) }}">
                                {{ $material->material_type }}
                            </span>
                        </td>
                        <td>{{ $material->unit }}</td>
                        <td>{{ number_format($material->unit_cost, 2) }}</td>
                        <td>
                            @php
                                $stock = $material->inventory->stock_level ?? 0;
                                $reorder = $material->inventory->reorder_level ?? 0;
                                $isLowStock = $stock <= $reorder;
                            @endphp
                            <span class="badge {{ $isLowStock ? 'stock-low' : 'stock-ok' }}"
                                  @if($isLowStock) title="⚠️ Stock is below reorder level!" @endif>
                                {{ $material->inventory->stock_level ?? 'N/A' }}
                            </span>
                        </td>
                        <td>{{ $material->inventory->reorder_level ?? 'N/A' }}</td>
                        <td>{{ $material->inventory->remarks ?? '' }}</td>
                        <td class="actions">
                            <a href="{{ route('admin.materials.edit', $material->material_id) }}" class="btn btn-sm btn-warning">✏️ Edit</a>
                            <form action="{{ route('admin.materials.destroy', $material->material_id) }}" method="POST" style="display:inline;">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to delete this material?');">🗑️ Delete</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" class="text-center">No materials found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
