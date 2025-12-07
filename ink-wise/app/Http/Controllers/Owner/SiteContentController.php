<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Admin\SiteContentController as AdminSiteContentController;
use App\Models\SiteSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SiteContentController extends AdminSiteContentController
{
    /**
     * Display the site content editor within the owner dashboard.
     */
    public function edit(): View
    {
        $settings = SiteSetting::current();

        return view('owner.settings.site-content', compact('settings'));
    }

    /**
     * Update the site content for owners.
     */
    public function update(Request $request): RedirectResponse
    {
        // Call the parent update method
        $response = parent::update($request);

        // Override the success message for owners
        return back()->with('status', 'Site content updated successfully.');
    }
}
