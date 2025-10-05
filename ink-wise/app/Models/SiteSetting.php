<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class SiteSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'contact_heading',
        'contact_subheading',
        'contact_company',
        'contact_address',
        'contact_phone',
        'contact_email',
        'contact_hours',
        'about_heading',
        'about_body',
    ];

    protected static string $cacheKey = 'site_settings.current';

    public static function current(): self
    {
        return Cache::remember(static::$cacheKey, now()->addMinutes(30), function () {
            return static::query()->first() ?? static::makeDefault();
        });
    }

    public static function forgetCache(): void
    {
        Cache::forget(static::$cacheKey);
    }

    protected static function makeDefault(): self
    {
        return new static([
            'contact_heading'    => 'Contact Us',
            'contact_subheading' => 'Have questions or want to place a custom order? Reach out to us anytime!',
            'contact_company'    => 'Merwen Printing Services – InkWise',
            'contact_address'    => '123 Rue de Paris, 75001 Paris, France',
            'contact_phone'      => '+33 1 23 45 67 89',
            'contact_email'      => 'InkwiseSystem@gmail.com',
            'contact_hours'      => "Monday – Saturday: 9:00 AM – 7:00 PM",
            'about_heading'      => 'About Us',
            'about_body'         => 'InkWise is your trusted partner in creating elegant and personalized invitations and giveaways. Our mission is to bring your special moments to life with creativity and style.',
        ]);
    }
}
