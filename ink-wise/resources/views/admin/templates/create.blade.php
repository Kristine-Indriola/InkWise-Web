@extends('layouts.admin')

@section('title', 'Create Template')

@section('content')
<div class="max-w-3xl mx-auto bg-white shadow-lg rounded-2xl p-6">
    <h1 class="text-2xl font-bold mb-6">Create New Template</h1>

    @if ($errors->any())
        <div class="bg-red-100 text-red-700 p-3 rounded mb-4">
            <ul class="list-disc pl-5">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('admin.templates.store') }}" method="POST" enctype="multipart/form-data" class="space-y-4">
        @csrf

        <div>
            <label for="name" class="block font-medium">Template Name</label>
            <input type="text" id="name" name="name" 
                   class="w-full border rounded-lg p-2" 
                   value="{{ old('name') }}" required>
        </div>

        <div>
            <label for="category" class="block font-medium">Category</label>
            <input type="text" id="category" name="category" 
                   class="w-full border rounded-lg p-2" 
                   value="{{ old('category') }}" required>
        </div>

        <div>
            <label for="file" class="block font-medium">Upload File (optional)</label>
            <input type="file" id="file" name="file" class="w-full border rounded-lg p-2">
        </div>

        <div class="flex justify-end space-x-3">
            <a href="{{ route('admin.templates.index') }}" class="px-4 py-2 bg-gray-300 rounded-lg">Cancel</a>
            <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg">Save Template</button>
        </div>
    </form>
</div>
@endsection
