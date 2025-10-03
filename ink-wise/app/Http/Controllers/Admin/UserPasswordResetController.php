<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\View\View;

class UserPasswordResetController extends Controller
{
	private const SESSION_KEY = 'admin.password_reset_unlocked_at';
	private const SESSION_TTL_SECONDS = 900; // 15 minutes

	public function index(Request $request): View
	{
		$this->ensureAdmin();

		$unlocked = $this->isUnlocked($request);
		$users = $unlocked ? $this->buildUserQuery($request)->paginate(12)->withQueryString() : collect();

		return view('admin.users.password-resets', [
			'unlocked' => $unlocked,
			'users' => $users,
			'search' => $request->input('search'),
		]);
	}

	public function unlock(Request $request): RedirectResponse
	{
		$this->ensureAdmin();

		$request->validate([
			'password' => ['required', 'string'],
		]);

		$user = Auth::user();

		if (! $user || ! Hash::check($request->input('password'), $user->password)) {
			return back()->withErrors(['password' => 'The password you entered is incorrect.']);
		}

		$request->session()->put(self::SESSION_KEY, now()->getTimestamp());

		return redirect()->route('admin.users.passwords.index')
			->with('status', 'Unlock granted. You can now send password reset links for the next 15 minutes.');
	}

	public function lock(Request $request): RedirectResponse
	{
		$this->ensureAdmin();

		$request->session()->forget(self::SESSION_KEY);

		return redirect()->route('admin.users.passwords.index')
			->with('status', 'Access locked. Re-enter your password when you need to send reset links again.');
	}

	public function send(Request $request, User $user): RedirectResponse
	{
		$this->ensureAdmin();

		if (! $this->isUnlocked($request)) {
			return redirect()->route('admin.users.passwords.index')
				->withErrors(['unlock' => 'Unlock the reset console before sending links.']);
		}

		if ($user->role === 'customer') {
			return redirect()->route('admin.users.passwords.index')
				->withErrors(['user' => 'Customer accounts must use the self-service reset page.']);
		}

		$status = Password::broker()->sendResetLink([
			'email' => $user->email,
		]);

		if ($status === Password::RESET_LINK_SENT) {
			return redirect()->route('admin.users.passwords.index')
				->with('status', 'Password reset email dispatched to '.$user->email.'.');
		}

		return redirect()->route('admin.users.passwords.index')
			->withErrors(['email' => __($status)]);
	}

	private function ensureAdmin(): void
	{
		$user = Auth::user();

		if (! $user || $user->role !== 'admin') {
			abort(403, 'Only administrators can access the password reset console.');
		}
	}

	private function isUnlocked(Request $request): bool
	{
		$timestamp = $request->session()->get(self::SESSION_KEY);

		if (! $timestamp) {
			return false;
		}

		$delta = now()->getTimestamp() - (int) $timestamp;
		if ($delta > self::SESSION_TTL_SECONDS) {
			$request->session()->forget(self::SESSION_KEY);
			return false;
		}

		return true;
	}

	private function buildUserQuery(Request $request)
	{
		$query = User::query()
			->where('role', '!=', 'customer')
			->with(['staff']);

		if ($term = $request->input('search')) {
			$query->where(function ($builder) use ($term) {
				$builder
					->where('email', 'like', "%{$term}%")
					->orWhere('role', 'like', "%{$term}%")
					->orWhereHas('staff', function ($staffQuery) use ($term) {
						$staffQuery->where('first_name', 'like', "%{$term}%")
							->orWhere('last_name', 'like', "%{$term}%");
					});
			});
		}

		return $query->orderBy('email');
	}
}
