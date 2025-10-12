@extends('layouts.admin')

@section('title', 'Site Content')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/admin-css/site-content.css') }}">
@endpush

@section('content')
<div class="page-header">
  <div>
    <h1 class="page-title">Site Content</h1>
    <p class="page-subtitle">Update the information displayed on the customer "About" and "Contact" sections.</p>
  </div>
  <div class="header-actions">
    <span class="last-updated">Last saved: {{ optional($settings->updated_at)->timezone(config('app.timezone', 'UTC'))?->format('M d, Y h:i A') ?? 'Not saved yet' }}</span>
  </div>
</div>

@if(session('status'))
  <div class="alert alert-success" role="status">
    <i class="fi fi-rr-badge-check" aria-hidden="true"></i>
    <span>{{ session('status') }}</span>
  </div>
@endif

<form action="{{ route('admin.settings.site-content.update') }}" method="POST" class="content-form" novalidate>
  @csrf
  @method('PUT')

  <section class="card">
    <header class="card-header">
      <div>
        <h2>Contact Section</h2>
        <p>Controls the "Contact Us" block and contact information displayed to customers.</p>
      </div>
    </header>
    <div class="card-body grid-2">
      <div class="form-field">
        <label for="contact_heading">Heading <span class="required">*</span></label>
        <input type="text" id="contact_heading" name="contact_heading" value="{{ old('contact_heading', $settings->contact_heading) }}" required>
        @error('contact_heading')
          <p class="field-error">{{ $message }}</p>
        @enderror
      </div>

      <div class="form-field">
        <label for="contact_company">Company name</label>
        <input type="text" id="contact_company" name="contact_company" value="{{ old('contact_company', $settings->contact_company) }}">
        @error('contact_company')
          <p class="field-error">{{ $message }}</p>
        @enderror
      </div>

      <div class="form-field grid-span-full">
        <label for="contact_subheading">Intro text</label>
        <textarea id="contact_subheading" name="contact_subheading" rows="3">{{ old('contact_subheading', $settings->contact_subheading) }}</textarea>
        @error('contact_subheading')
          <p class="field-error">{{ $message }}</p>
        @enderror
      </div>

      <div class="form-field">
        <label for="contact_address">Address</label>
        <input type="text" id="contact_address" name="contact_address" value="{{ old('contact_address', $settings->contact_address) }}">
        @error('contact_address')
          <p class="field-error">{{ $message }}</p>
        @enderror
      </div>

      <div class="form-field">
        <label for="contact_phone">Phone</label>
        <input type="text" id="contact_phone" name="contact_phone" value="{{ old('contact_phone', $settings->contact_phone) }}">
        @error('contact_phone')
          <p class="field-error">{{ $message }}</p>
        @enderror
      </div>

      <div class="form-field">
        <label for="contact_email">Email</label>
        <input type="email" id="contact_email" name="contact_email" value="{{ old('contact_email', $settings->contact_email) }}">
        @error('contact_email')
          <p class="field-error">{{ $message }}</p>
        @enderror
      </div>

      <div class="form-field grid-span-full">
        <label for="contact_hours">Business hours</label>
        <textarea id="contact_hours" name="contact_hours" rows="3" placeholder="Use line breaks to separate days.">{{ old('contact_hours', $settings->contact_hours) }}</textarea>
        @error('contact_hours')
          <p class="field-error">{{ $message }}</p>
        @enderror
      </div>
    </div>
  </section>

  <section class="card">
    <header class="card-header">
      <div>
        <h2>About Section</h2>
        <p>Controls the text shown in the customer "About Us" banner.</p>
      </div>
    </header>
    <div class="card-body">
      <div class="form-field">
        <label for="about_heading">Heading <span class="required">*</span></label>
        <input type="text" id="about_heading" name="about_heading" value="{{ old('about_heading', $settings->about_heading) }}" required>
        @error('about_heading')
          <p class="field-error">{{ $message }}</p>
        @enderror
      </div>

      <div class="form-field">
        <label for="about_body">Description <span class="required">*</span></label>
        <textarea id="about_body" name="about_body" rows="5">{{ old('about_body', $settings->about_body) }}</textarea>
        @error('about_body')
          <p class="field-error">{{ $message }}</p>
        @enderror
      </div>
    </div>
  </section>

  <div class="form-actions">
    <button type="submit" class="btn-primary">
      <i class="fi fi-rr-disk" aria-hidden="true"></i>
      <span>Save changes</span>
    </button>
  </div>
</form>
@endsection
