<?php

namespace Tests\Feature;

use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class MessageTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_send_message_in_conversation(): void
    {
        Event::fake();

        $user1 = User::create(['username' => 'neo', 'password' => Hash::make('password')]);
        $user2 = User::create(['username' => 'trinity', 'password' => Hash::make('password')]);

        $conv = Conversation::create(['type' => 'direct', 'created_by' => $user1->id]);
        $conv->users()->attach([$user1->id, $user2->id]);

        $this->actingAs($user1);

        $response = $this->postJson("/api/conversations/{$conv->id}/messages", [
            'body' => 'Mensaje secreto encriptado',
            'type' => 'text',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('message.body', 'Mensaje secreto encriptado');

        $this->assertDatabaseHas('messages', [
            'conversation_id' => $conv->id,
            'sender_id' => $user1->id,
        ]);

        $this->assertEquals('Mensaje secreto encriptado', $conv->messages()->first()->body);
    }

    public function test_user_can_retrieve_messages(): void
    {
        $user1 = User::create(['username' => 'neo', 'password' => Hash::make('password')]);
        $user2 = User::create(['username' => 'trinity', 'password' => Hash::make('password')]);

        $conv = Conversation::create(['type' => 'direct', 'created_by' => $user1->id]);
        $conv->users()->attach([$user1->id, $user2->id]);

        Message::create([
            'conversation_id' => $conv->id,
            'sender_id' => $user1->id,
            'body' => 'Hola Trinity',
        ]);

        $this->actingAs($user2);

        $response = $this->getJson("/api/conversations/{$conv->id}/messages");

        $response->assertStatus(200)
            ->assertJsonCount(1, 'messages');
    }
}
