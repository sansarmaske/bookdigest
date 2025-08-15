<?php

namespace Tests\Feature\Auth;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_screen_can_be_rendered(): void
    {
        $response = $this->get('/register');

        $response->assertStatus(200);
    }

    public function test_new_users_can_register(): void
    {
        $response = $this->post('/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        // Verify user was created
        $user = \App\Models\User::where('email', 'test@example.com')->first();
        $this->assertNotNull($user);
        $this->assertEquals('Test User', $user->name);

        // Check authentication state
        $this->assertAuthenticated();
        $this->assertEquals($user->id, auth()->id());

        // Verify the user's email to allow access to dashboard
        $user->markEmailAsVerified();

        $response->assertRedirect(route('dashboard', absolute: false));
    }
}
