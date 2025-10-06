<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;

class SiteSetting extends Model
{
	use HasFactory;

	/**
	 * The attributes that are mass assignable.
	 *
	 * @var array<int, string>
	 */
	protected $fillable = [
		'contact_heading',
		'contact_company',
		'contact_subheading',
		'contact_address',
		'contact_phone',
		'contact_email',
		'contact_hours',
		'about_heading',
		'about_body',
	];

	/**
	 * Returns the singleton record containing the public site settings.
	 */
	public static function current(): self
	{
		return Cache::remember('site_settings.current', 600, function () {
			$settings = static::first();

			if (! $settings) {
				$settings = static::create(static::defaults());
			} else {
				$settings->syncMissingDefaults();

				if ($settings->isDirty()) {
					$settings->save();
				}
			}

			return $settings;
		});
	}

	/**
	 * Default values used when bootstrapping the table.
	 *
	 * @return array<string, mixed>
	 */
	public static function defaults(): array
	{
		return [
			'contact_heading'    => 'Contact Us',
			'contact_company'    => 'Merwen Printing Services – InkWise',
			'contact_subheading' => 'Have questions or want to place a custom order? Reach out to us anytime!',
			'contact_address'    => '123 Rue de Paris, 75001 Paris, France',
			'contact_phone'      => '+33 1 23 45 67 89',
			'contact_email'      => 'InkwiseSystem@gmail.com',
			'contact_hours'      => "Monday – Saturday: 9:00 AM – 7:00 PM",
			'about_heading'      => 'About Us',
			'about_body'         => 'InkWise is your trusted partner in creating elegant and personalized invitations and giveaways. Our mission is to bring your special moments to life with creativity and style.',
		];
	}

	/**
	 * Normalised contact hours lines for iteration in templates.
	 *
	 * @return array<int, string>
	 */
	public function contactHoursLines(): array
	{
		$hours = $this->contact_hours;

		if (blank($hours)) {
			return [];
		}

		$lines = preg_split("/(\r\n|\r|\n)/", $hours) ?: [];

		return array_values(array_filter(array_map('trim', $lines)));
	}

	/**
	 * Convenience method to refresh defaults in case schema extends.
	 */
	public function syncMissingDefaults(): void
	{
		$missing = array_diff_key(static::defaults(), $this->getAttributes());

		if ($missing) {
			$this->fill(Arr::only(static::defaults(), array_keys($missing)));
		}
	}
}
