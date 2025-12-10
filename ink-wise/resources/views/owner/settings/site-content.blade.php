@extends('layouts.owner.app')

@section('title', 'Site Content')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/admin-css/site-content.css') }}">
<style>
.owner-site-content-clone-active {
  background: #f4f6f9 !important;
  color: #0f172a;
}

.owner-site-content-clone-wrapper,
.owner-site-content-clone-wrapper * {
  font-family: 'Nunito', sans-serif !important;
  box-sizing: border-box;
}

.owner-site-content-clone-wrapper {
  position: relative;
  z-index: 1;
}

.owner-site-content-clone-wrapper::before {
  content: '';
  position: fixed;
  inset: 0;
  background: linear-gradient(120deg, #eef2ff 0%, #fef3f7 50%, #f0f9ff 100%);
  z-index: -1;
}

.content-preview {
  background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
  border: 1px solid #e2e8f0;
  border-radius: 12px;
  padding: 16px;
  margin-top: 8px;
  font-size: 13px;
  color: #64748b;
}

.field-icon {
  display: inline-block;
  width: 16px;
  height: 16px;
  margin-right: 6px;
  vertical-align: middle;
}

.section-badge {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  background: linear-gradient(135deg, #3b82f6, #1d4ed8);
  color: white;
  padding: 4px 12px;
  border-radius: 20px;
  font-size: 12px;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.form-section {
  position: relative;
  overflow: hidden;
}

.form-section::before {
  content: '';
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  height: 4px;
  background: linear-gradient(90deg, #3b82f6, #8b5cf6, #ec4899);
}

.contact-section::before {
  background: linear-gradient(90deg, #3b82f6, #1d4ed8);
}

.about-section::before {
  background: linear-gradient(90deg, #8b5cf6, #7c3aed);
}

.owner-site-content-clone-wrapper {
  margin-left: var(--owner-sidebar-width, 230px);
  padding: calc(var(--owner-topbar-height, 64px) + 24px) clamp(8px, 6vw, 28px) 32px;
  min-height: 100vh;
  background: #f4f6f9;
  transition: margin-left 0.32s ease;
}

body.sidebar-collapsed .owner-site-content-clone-wrapper {
  margin-left: var(--owner-sidebar-collapsed-width, 70px);
}

.owner-site-content-clone {
  max-width: 1440px;
  margin: 0 auto;
  width: 100%;
  background: transparent;
}

.owner-site-content-clone .page-header {
  margin-bottom: 32px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 24px;
}

.owner-site-content-clone .content-form {
  background: #ffffff;
  border-radius: 18px;
  border: 1px solid rgba(148, 185, 255, 0.24);
  box-shadow: 0 18px 36px rgba(15, 23, 42, 0.08);
  padding: 32px;
}

.owner-site-content-clone .page-title {
  font-size: clamp(28px, 4vw, 36px);
  font-weight: 800;
  margin-bottom: 8px;
  color: #0f172a;
}

.owner-site-content-clone .page-subtitle {
  font-size: 16px;
  color: #475569;
  margin: 0;
}

.owner-site-content-clone .header-actions .last-updated {
  font-size: 13px;
  color: #64748b;
  background: rgba(255, 255, 255, 0.8);
  border: 1px solid rgba(148, 163, 184, 0.4);
  border-radius: 999px;
  padding: 8px 16px;
  font-weight: 600;
}

.owner-site-content-clone .alert {
  border: none;
  border-radius: 16px;
  background: linear-gradient(135deg, #4facfe, #00f2fe);
  color: #fff;
  box-shadow: 0 12px 32px rgba(15, 23, 42, 0.18);
}

.owner-site-content-clone .card {
  border: none;
  border-radius: 24px;
  background: #fff;
  box-shadow: 0 28px 40px rgba(15, 23, 42, 0.12);
  margin-bottom: 32px;
}

.owner-site-content-clone .card-header {
  border-bottom: none;
  padding: 32px 32px 0;
}

.owner-site-content-clone .card-body {
  padding: 32px;
}

.owner-site-content-clone .form-field input,
.owner-site-content-clone .form-field textarea {
  border: 2px solid #e5e7eb;
  border-radius: 16px;
  padding: 16px 18px;
  font-size: 14px;
  transition: all 0.2s ease;
  background: #fff;
}

.owner-site-content-clone .form-field input:focus,
.owner-site-content-clone .form-field textarea:focus {
  border-color: #6366f1;
  box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.12);
}

.owner-site-content-clone .btn-primary {
  background: linear-gradient(135deg, #6366f1, #8b5cf6);
  border: none;
  border-radius: 999px;
  padding: 16px 32px;
  font-size: 15px;
  font-weight: 700;
  box-shadow: 0 18px 30px rgba(99, 102, 241, 0.35);
  transition: transform 0.2s ease, box-shadow 0.2s ease;
}

.owner-site-content-clone .btn-primary:hover {
  transform: translateY(-2px);
  box-shadow: 0 24px 36px rgba(99, 102, 241, 0.4);
}

.owner-site-content-clone .form-actions {
  border-radius: 24px;
  border: 1px solid rgba(148, 163, 184, 0.35);
  background: linear-gradient(135deg, #eef2ff, #f8fafc);
}


.owner-site-content-clone-active .content-form .field-help {
  color: #64748b;
}

body.owner-layout {
  background: #f4f6f9;
  font-family: 'Nunito', sans-serif;
  color: #0f172a;
}

@media (max-width: 1024px) {
  .owner-site-content-clone-wrapper {
    margin-left: 0;
    padding: calc(var(--owner-topbar-height, 64px) + 16px) clamp(12px, 4vw, 24px) 28px;
  }
}

@media (max-width: 600px) {
  .owner-site-content-clone .content-form {
    padding: 20px;
  }
}
</style>
@endpush

@section('content')
@include('layouts.owner.sidebar')

<section class="owner-site-content-clone-wrapper" role="main">
  <div class="owner-site-content-clone">
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

<form action="{{ route('owner.settings.site-content.update') }}" method="POST" class="content-form" novalidate>
  @csrf
  @method('PUT')

  <section class="card form-section contact-section">
    <header class="card-header">
      <div>
        <span class="section-badge">
          <svg class="field-icon" fill="currentColor" viewBox="0 0 20 20">
            <path fill-rule="evenodd" d="M5.05 4.05a7 7 0 119.9 9.9L10 18.9l-4.95-4.95a7 7 0 010-9.9zM10 11a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd"></path>
          </svg>
          Contact Section
        </span>
        <h2>Contact Information</h2>
        <p>Manage the contact details and information displayed to your customers.</p>
      </div>
    </header>
    <div class="card-body" style="display: grid; grid-template-columns: 1fr 2fr; gap: 24px;">
      <div class="content-preview">
        <h4 style="margin: 0 0 12px 0; color: #1e293b; font-size: 14px;">📋 Live Preview</h4>
        <div style="font-size: 13px; line-height: 1.5;">
          <strong>{{ $settings->contact_heading ?: 'Your Heading Here' }}</strong><br>
          <em>{{ $settings->contact_subheading ?: 'Your intro text appears here...' }}</em><br><br>
          <strong>{{ $settings->contact_company ?: 'Company Name' }}</strong><br>
          📍 {{ $settings->contact_address ?: '123 Main St, City, State' }}<br>
          📞 {{ $settings->contact_phone ?: '(555) 123-4567' }}<br>
          ✉️ {{ $settings->contact_email ?: 'info@company.com' }}<br>
          🕒 {{ $settings->contact_hours ?: 'Mon-Fri: 9AM-5PM' }}
        </div>
      </div>

      <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 20px;">
      </div>

      <div class="form-field">
        <label for="contact_heading">Heading <span class="required">*</span></label>
        <input type="text" id="contact_heading" name="contact_heading" value="{{ old('contact_heading', $settings->contact_heading) }}" required>
        <small class="field-help">This is the main heading displayed at the top of the Contact Us section on the customer site.</small>
        @error('contact_heading')
          <p class="field-error">{{ $message }}</p>
        @enderror
      </div>

      <div class="form-field">
        <label for="contact_company">Company name</label>
        <input type="text" id="contact_company" name="contact_company" value="{{ old('contact_company', $settings->contact_company) }}">
        <small class="field-help">This appears as the company name in the contact information section on the customer site.</small>
        @error('contact_company')
          <p class="field-error">{{ $message }}</p>
        @enderror
      </div>

      <div class="form-field grid-span-full">
        <label for="contact_subheading">Intro text</label>
        <textarea id="contact_subheading" name="contact_subheading" rows="3">{{ old('contact_subheading', $settings->contact_subheading) }}</textarea>
        <small class="field-help">This introductory text appears below the heading in the Contact Us section on the customer site.</small>
        @error('contact_subheading')
          <p class="field-error">{{ $message }}</p>
        @enderror
      </div>

      <div class="form-field">
        <label for="contact_address">Address</label>
        <input type="text" id="contact_address" name="contact_address" value="{{ old('contact_address', $settings->contact_address) }}">
        <small class="field-help">This address appears with a 📍 icon in the contact information section on the customer site.</small>
        @error('contact_address')
          <p class="field-error">{{ $message }}</p>
        @enderror
      </div>

      <div class="form-field">
        <label for="contact_phone">Phone</label>
        <input type="text" id="contact_phone" name="contact_phone" value="{{ old('contact_phone', $settings->contact_phone) }}">
        <small class="field-help">This phone number appears with a 📞 icon in the contact information section on the customer site.</small>
        @error('contact_phone')
          <p class="field-error">{{ $message }}</p>
        @enderror
      </div>

      <div class="form-field">
        <label for="contact_email">Email</label>
        <input type="email" id="contact_email" name="contact_email" value="{{ old('contact_email', $settings->contact_email) }}">
        <small class="field-help">This email address appears with a ✉️ icon in the contact information section on the customer site.</small>
        @error('contact_email')
          <p class="field-error">{{ $message }}</p>
        @enderror
      </div>

      <div class="form-field grid-span-full">
        <label for="contact_hours">Business hours</label>
        <textarea id="contact_hours" name="contact_hours" rows="3" placeholder="Use line breaks to separate days.">{{ old('contact_hours', $settings->contact_hours) }}</textarea>
        <small class="field-help">These business hours appear with a 🕒 icon in the contact information section on the customer site. Use line breaks to separate days.</small>
        @error('contact_hours')
          <p class="field-error">{{ $message }}</p>
        @enderror
      </div>
    </div>
  </section>

  <section class="card form-section about-section">
    <header class="card-header">
      <div>
        <span class="section-badge">
          <svg class="field-icon" fill="currentColor" viewBox="0 0 20 20">
            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path>
          </svg>
          About Section
        </span>
        <h2>About Your Business</h2>
        <p>Customize the about section that tells customers who you are.</p>
      </div>
    </header>
    <div class="card-body" style="display: grid; grid-template-columns: 1fr 2fr; gap: 24px;">
      <div class="content-preview">
        <h4 style="margin: 0 0 12px 0; color: #1e293b; font-size: 14px;">📋 Live Preview</h4>
        <div style="font-size: 13px; line-height: 1.5;">
          <strong>{{ $settings->about_heading ?: 'About Our Company' }}</strong><br>
          <span style="color: #64748b;">{{ $settings->about_body ?: 'Tell your customers about your business, mission, and what makes you unique...' }}</span>
        </div>
      </div>

      <div>
      <div class="form-field">
        <label for="about_heading">Heading <span class="required">*</span></label>
        <input type="text" id="about_heading" name="about_heading" value="{{ old('about_heading', $settings->about_heading) }}" required>
        <small class="field-help">This is the main heading displayed in the About Us section on the customer site.</small>
        @error('about_heading')
          <p class="field-error">{{ $message }}</p>
        @enderror
      </div>

      <div class="form-field">
        <label for="about_body">Description <span class="required">*</span></label>
        <textarea id="about_body" name="about_body" rows="5">{{ old('about_body', $settings->about_body) }}</textarea>
        <small class="field-help">This description text appears below the heading in the About Us section on the customer site.</small>
        @error('about_body')
          <p class="field-error">{{ $message }}</p>
        @enderror
      </div>
    </div>
  </section>

  <div class="form-actions" style="background: #f8fafc; padding: 24px; border-radius: 16px; margin-top: 32px; border: 1px solid #e2e8f0;">
    <div style="display: flex; align-items: center; justify-content: space-between; gap: 16px;">
      <div style="font-size: 14px; color: #64748b;">
        <strong>💡 Tip:</strong> Changes will be visible immediately on your customer-facing site after saving.
      </div>
      <button type="submit" class="btn-primary" style="box-shadow: 0 4px 14px rgba(59, 130, 246, 0.3);">
        <svg class="field-icon" fill="currentColor" viewBox="0 0 20 20" style="width: 18px; height: 18px;">
          <path d="M7.707 10.293a1 1 0 10-1.414 1.414l3 3a1 1 0 001.414 0l3-3a1 1 0 00-1.414-1.414L11 11.586V7a1 1 0 10-2 0v4.586l-1.293-1.293z"></path>
          <path d="M5 4a2 2 0 012-2h6a2 2 0 012 2v14l-5-2.5L5 18V4z"></path>
        </svg>
        <span>Save All Changes</span>
      </button>
    </div>
  </div>
  </form>
    </div>
  </section>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
  document.body.classList.add('owner-site-content-clone-active');

  window.addEventListener('beforeunload', function() {
    document.body.classList.remove('owner-site-content-clone-active');
  });

    // Real-time preview updates
    const fields = [
        { input: 'contact_heading', preview: '.contact-section .content-preview strong' },
        { input: 'contact_subheading', preview: '.contact-section .content-preview em' },
        { input: 'contact_company', preview: '.contact-section .content-preview strong:nth-of-type(2)' },
        { input: 'contact_address', preview: '.contact-section .content-preview span:nth-of-type(1)' },
        { input: 'contact_phone', preview: '.contact-section .content-preview span:nth-of-type(2)' },
        { input: 'contact_email', preview: '.contact-section .content-preview span:nth-of-type(3)' },
        { input: 'contact_hours', preview: '.contact-section .content-preview span:nth-of-type(4)' },
        { input: 'about_heading', preview: '.about-section .content-preview strong' },
        { input: 'about_body', preview: '.about-section .content-preview span' }
    ];

    fields.forEach(field => {
        const input = document.getElementById(field.input);
        const preview = document.querySelector(field.preview);
        
        if (input && preview) {
            input.addEventListener('input', function() {
                const value = this.value || this.placeholder || 'Not set';
                preview.textContent = value;
            });
        }
    });

    // Add visual feedback on form submission
    const form = document.querySelector('.content-form');
    form.addEventListener('submit', function() {
        const button = form.querySelector('.btn-primary');
        const originalText = button.innerHTML;
        button.innerHTML = '<span>Saving...</span>';
        button.disabled = true;
        
        setTimeout(() => {
            button.innerHTML = originalText;
            button.disabled = false;
        }, 2000);
    });
});
</script>
@endpush
