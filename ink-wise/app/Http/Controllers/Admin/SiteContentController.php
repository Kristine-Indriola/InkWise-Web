<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SiteSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;

class SiteContentController extends Controller
{
	/**
	 * Display the form for editing the public-facing site content.
	 */
	public function edit(): View
	{
	logger('SiteContentController::edit invoked');

		$settings = SiteSetting::current();

		return view('admin.settings.site-content', compact('settings'));
	}

	/**
	 * Persist the updated site content.
	 */
	public function update(Request $request): RedirectResponse
	{
		$settings = SiteSetting::current();

		$validated = $request->validate([
			'contact_heading'    => ['required', 'string', 'max:255'],
			'contact_company'    => ['nullable', 'string', 'max:255'],
			'contact_subheading' => ['nullable', 'string'],
			'contact_address'    => ['nullable', 'string', 'max:255'],
			'contact_phone'      => ['nullable', 'string', 'max:255'],
			'contact_email'      => ['nullable', 'email', 'max:255'],
			'contact_hours'      => ['nullable', 'string'],
			'about_heading'      => ['required', 'string', 'max:255'],
			'about_body'         => ['required', 'string'],
		]);

		$settings->fill($validated)->save();

		Cache::forget('site_settings.current');

		// Prime cache with fresh copy so public pages reflect changes immediately.
		SiteSetting::current();

		return back()->with('status', 'Site content updated successfully.');
	}
}
