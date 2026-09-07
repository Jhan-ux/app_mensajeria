<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AnonymousAuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_register_with_username_and_password(): void
    {
        $response = $this->post('/register', [
            'username' => 'fantasma',
            'display_name' => 'El Fantasma',
            'password' => 'secreto123',
            'password_confirmation' => 'secreto123',
        ]);

        $response->assertRedirect('/chat');
        $this->assertAuthenticated();
        $this->assertDatabaseHas('users', [
            'username' => 'fantasma',
            'display_name' => 'El Fantasma',
        ]);
    }

    public function test_cannot_register_with_duplicate_username(): void
    {
        User::create([
            'username' => 'fantasma',
            'password' => Hash::make('password'),
        ]);

        $response = $this->post('/register', [
            'username' => 'fantasma',
            'password' => 'secreto123',
            'password_confirmation' => 'secreto123',
        ]);

        $response->assertSessionHasErrors('username');
    }

    public function test_user_can_login_with_username_and_password(): void
    {
        $user = User::create([
            'username' => 'agente_x',
            'password' => Hash::make('claveSecreta'),
        ]);

        $response = $this->post('/login', [
            'username' => 'agente_x',
            'password' => 'claveSecreta',
        ]);

        $response->assertRedirect('/chat');
        $this->assertAuthenticatedAs($user);
    }

    public function test_user_can_register_with_custom_avatar_url(): void
    {
        $customAvatar = 'https://images.unsplash.com/photo-1535713875002-d1d0cf377fde';

        $response = $this->post('/register', [
            'username' => 'shadow_lord',
            'display_name' => 'Shadow',
            'avatar_url' => $customAvatar,
            'password' => 'secreto123',
            'password_confirmation' => 'secreto123',
        ]);

        $response->assertRedirect('/chat');
        $this->assertDatabaseHas('users', [
            'username' => 'shadow_lord',
            'avatar' => $customAvatar,
        ]);
    }

    public function test_user_can_update_profile_avatar_url(): void
    {
        $user = User::create([
            'username' => 'agent_avatar',
            'password' => Hash::make('password123'),
        ]);

        $newAvatar = 'https://images.unsplash.com/photo-1570295999919-56ceb5ecca61';

        $response = $this->actingAs($user)->postJson('/api/profile', [
            'display_name' => 'Avatar Agent',
            'status_message' => 'Invisible',
            'avatar_url' => $newAvatar,
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('user.avatar_url', $newAvatar);

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'avatar' => $newAvatar,
        ]);
    }

    public function test_user_can_logout(): void
    {
        $user = User::create([
            'username' => 'agente_x',
            'password' => Hash::make('claveSecreta'),
        ]);

        $this->actingAs($user);
        $response = $this->post('/logout');

        $response->assertRedirect('/login');
        $this->assertGuest();
    }
}
