@extends('layouts.admin')

@section('content')
    <h1>Create Template</h1>

    <form method="POST" action="{{ route('admin.templates.store') }}" enctype="multipart/form-data">
        @csrf
        <label>Title</label>
        <input type="text" name="title" required><br><br>

        <label>Type</label>
        <select name="type" required>
            <option value="invitation">Invitation</option>
            <option value="giveaway">Giveaway</option>
        </select><br><br>

        <label>Upload File</label>
        <input type="file" name="file" required><br><br>

        <button type="submit">Save Template</button>
    </form>

     <h1>Templates</h1>
    <a href="{{ route('admin.templates.create') }}" class="btn btn-primary">+ Add Template</a>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    
@endsection
