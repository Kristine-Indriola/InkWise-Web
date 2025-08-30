@extends('layouts.admin')

@section('title', 'Templates')

@section('content')
<div class="templates-container">
    <h1>Manage Templates</h1>

    <a href="{{ route('admin.templates.create') }}" class="btn btn-primary">➕ Create New Template</a>

    <p>Template listing will go here...</p>
</div>
@endsection
