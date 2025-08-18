@extends('layouts.admin')

@section('title', 'Templates')

@section('content')
<div class="container">
    <h2>Templates</h2>
    <a href="{{ route('admin.templates.editor') }}" class="btn btn-primary mb-3">Create New Template</a>

    <ul class="list-group">
        @foreach($templates as $template)
            <li class="list-group-item d-flex justify-content-between">
                {{ $template->name }}
                <a href="{{ route('admin.templates.editor', $template->id) }}" class="btn btn-sm btn-info">Edit</a>
            </li>
        @endforeach
    </ul>
</div>
@endsection
