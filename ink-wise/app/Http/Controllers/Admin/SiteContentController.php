<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SiteSetting;
use App\Models\Staff;
use App\Models\User;
use App\Notifications\SiteContentUpdated;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Notification;
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

		$fields = array_keys($validated);
		$originalValues = $settings->only($fields);

		$settings->fill($validated);

		$changedFields = collect($fields)
			->filter(fn (string $field) => ($originalValues[$field] ?? null) !== $settings->getAttribute($field))
			->mapWithKeys(fn (string $field) => [$field => $settings->getAttribute($field)])
			->all();

		$settings->save();

		Cache::forget('site_settings.current');

		// Prime cache with fresh copy so public pages reflect changes immediately.
		SiteSetting::current();

		if (! empty($changedFields)) {
			$actor = $request->user();
			$actorName = $actor?->name ?? 'Administrator';
			$actorEmail = $actor?->email;
			$actorId = $actor?->getAuthIdentifier();

			$ownerUsers = Staff::query()
				->where('role', 'owner')
				->whereHas('user', fn ($query) => $query->whereNotNull('email'))
				->with('user')
				->get()
				->pluck('user')
				->filter()
				->unique(fn (User $user) => $user->getAuthIdentifier())
				->values();

			if ($ownerUsers->isNotEmpty()) {
				Notification::send(
					$ownerUsers,
					new SiteContentUpdated($actorName, $actorEmail, $actorId, $changedFields)
				);
			}
		}

		return back()->with('status', 'Site content updated successfully.');
	}
}
