@extends('layouts.app')

@section('content')
<div class="container">
    <h2>Edit Material</h2>

    <form method="POST" action="{{ route('admin.materials.update', $material->material_id) }}">
        @csrf
        @method('PUT')

        <label>Material Name:</label><br>
        <input type="text" name="material_name" value="{{ old('material_name', $material->material_name) }}" required><br><br>

        <label>Material Type:</label><br>
        <input type="text" name="material_type" value="{{ old('material_type', $material->material_type) }}" required><br><br>

        <label>Unit:</label><br>
        <input type="text" name="unit" value="{{ old('unit', $material->unit) }}" required><br><br>

        <label>Unit Cost:</label><br>
        <input type="number" step="0.01" name="unit_cost" value="{{ old('unit_cost', $material->unit_cost) }}" required><br><br>

        <button type="submit">Update Material</button>
    </form>
</div>
@endsection
