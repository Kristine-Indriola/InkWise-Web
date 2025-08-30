@extends('layouts.admin')

@section('title', 'Materials')

@section('content')
<div class="materials-container">
    <h1>Materials Management</h1>

    <a href="{{ route('admin.materials.create') }}" class="btn btn-primary">➕ Add New Material</a>

    @if(session('success'))
        <div class="alert alert-success mt-2">
            {{ session('success') }}
        </div>
    @endif

    <table class="table mt-3">
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
                    <td>{{ $material->material_type }}</td>
                    <td>{{ $material->unit }}</td>
                    <td>{{ number_format($material->unit_cost, 2) }}</td>
                    <td>{{ $material->inventory->stock_level ?? 'N/A' }}</td>
                    <td>{{ $material->inventory->reorder_level ?? 'N/A' }}</td>
                    <td>{{ $material->inventory->remarks ?? '' }}</td>
                    <td>
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
@endsection
