<?php

namespace Tests\Feature;

use App\Models\Conversation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ConversationTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_create_direct_chat_with_another_username(): void
    {
        $user1 = User::create(['username' => 'neo', 'password' => Hash::make('password')]);
        $user2 = User::create(['username' => 'trinity', 'password' => Hash::make('password')]);

        $this->actingAs($user1);

        $response = $this->postJson('/api/conversations', [
            'type' => 'direct',
            'username' => 'trinity',
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure(['conversation_id', 'is_new']);

        $this->assertDatabaseHas('conversations', [
            'type' => 'direct',
            'created_by' => $user1->id,
        ]);
    }

    public function test_creating_existing_direct_chat_returns_existing_id(): void
    {
        $user1 = User::create(['username' => 'neo', 'password' => Hash::make('password')]);
        $user2 = User::create(['username' => 'trinity', 'password' => Hash::make('password')]);

        $conv = Conversation::create(['type' => 'direct', 'created_by' => $user1->id]);
        $conv->users()->attach([$user1->id, $user2->id]);

        $this->actingAs($user1);

        $response = $this->postJson('/api/conversations', [
            'type' => 'direct',
            'username' => 'trinity',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'conversation_id' => $conv->id,
                'is_new' => false,
            ]);
    }

    public function test_user_can_create_group_chat(): void
    {
        $user1 = User::create(['username' => 'neo', 'password' => Hash::make('password')]);
        $user2 = User::create(['username' => 'trinity', 'password' => Hash::make('password')]);
        $user3 = User::create(['username' => 'morpheus', 'password' => Hash::make('password')]);

        $this->actingAs($user1);

        $response = $this->postJson('/api/conversations', [
            'type' => 'group',
            'title' => 'Nebuchadnezzar',
            'usernames' => ['trinity', 'morpheus'],
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('conversations', [
            'type' => 'group',
            'title' => 'Nebuchadnezzar',
        ]);
    }
}
