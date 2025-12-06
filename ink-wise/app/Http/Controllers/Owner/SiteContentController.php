<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Admin\SiteContentController as AdminSiteContentController;
use App\Models\SiteSetting;
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
}
