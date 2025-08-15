<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;

class SocialiteController extends Controller
{
    public function redirect($provider)
    {
        return Socialite::driver($provider)->redirect();
    }

    public function callback($provider)
    {
        try {
            Log::info('Google OAuth callback started', ['provider' => $provider]);

            $socialUser = Socialite::driver($provider)->user();
            Log::info('Social user retrieved', [
                'email' => $socialUser->getEmail(),
                'name' => $socialUser->getName(),
                'id' => $socialUser->getId(),
            ]);

            // Check if user already exists
            $user = User::where('email', $socialUser->getEmail())->first();

            if ($user) {
                Log::info('Existing user found', ['user_id' => $user->id]);
                // Update user with social info if needed
                if (empty($user->google_id) && $provider === 'google') {
                    $user->update([
                        'google_id' => $socialUser->getId(),
                    ]);
                    Log::info('Updated existing user with Google ID');
                }
            } else {
                Log::info('Creating new user');
                // Create new user
                $user = User::create([
                    'name' => $socialUser->getName(),
                    'email' => $socialUser->getEmail(),
                    'password' => bcrypt(str()->random(32)), // Random password for OAuth users
                    'google_id' => $provider === 'google' ? $socialUser->getId() : null,
                    'email_verified_at' => now(),
                ]);
                Log::info('New user created', ['user_id' => $user->id]);
            }

            Auth::login($user);
            Log::info('User logged in successfully', ['user_id' => $user->id]);

            return redirect()->intended('/books');
        } catch (\Exception $e) {
            Log::error('Google OAuth failed', [
                'provider' => $provider,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return redirect('/login')->with('error', 'Authentication failed. Please try again.');
        }
    }
}
