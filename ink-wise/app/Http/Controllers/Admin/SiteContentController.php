<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SiteSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SiteContentController extends Controller
{
    public function edit(): View
    {
        $settings = SiteSetting::current();

        return view('admin.settings.site-content', compact('settings'));
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'contact_heading'    => ['required', 'string', 'max:150'],
            'contact_subheading' => ['nullable', 'string'],
            'contact_company'    => ['nullable', 'string', 'max:150'],
            'contact_address'    => ['nullable', 'string', 'max:255'],
            'contact_phone'      => ['nullable', 'string', 'max:100'],
            'contact_email'      => ['nullable', 'email', 'max:150'],
            'contact_hours'      => ['nullable', 'string'],
            'about_heading'      => ['required', 'string', 'max:150'],
            'about_body'         => ['required', 'string'],
        ]);

        $settings = SiteSetting::query()->first();

        if ($settings) {
            $settings->update($data);
        } else {
            $settings = SiteSetting::create($data);
        }

        SiteSetting::forgetCache();

        return redirect()
            ->route('admin.settings.site-content.edit')
            ->with('status', 'Site content updated successfully.');
    }
}
