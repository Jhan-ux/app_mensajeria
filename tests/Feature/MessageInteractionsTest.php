<?php

namespace Tests\Feature;

use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MessageInteractionsTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_toggle_emoji_reaction_on_message(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        $conversation = Conversation::create(['type' => 'direct']);
        $conversation->users()->attach([$user1->id, $user2->id]);

        $message = Message::create([
            'conversation_id' => $conversation->id,
            'sender_id' => $user1->id,
            'body' => 'Mensaje para reaccionar',
        ]);

        // 1. Add reaction ❤️
        $response = $this->actingAs($user2)->postJson("/api/messages/{$message->id}/react", [
            'emoji' => '❤️',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'action' => 'added',
            ]);

        $this->assertDatabaseHas('message_reactions', [
            'message_id' => $message->id,
            'user_id' => $user2->id,
            'emoji' => '❤️',
        ]);

        // 2. Remove reaction (toggle)
        $response2 = $this->actingAs($user2)->postJson("/api/messages/{$message->id}/react", [
            'emoji' => '❤️',
        ]);

        $response2->assertStatus(200)
            ->assertJson([
                'action' => 'removed',
            ]);

        $this->assertDatabaseMissing('message_reactions', [
            'message_id' => $message->id,
            'user_id' => $user2->id,
            'emoji' => '❤️',
        ]);
    }

    public function test_user_can_pin_and_unpin_message(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        $conversation = Conversation::create(['type' => 'direct']);
        $conversation->users()->attach([$user1->id, $user2->id]);

        $message = Message::create([
            'conversation_id' => $conversation->id,
            'sender_id' => $user1->id,
            'body' => 'Mensaje anclado muy importante',
        ]);

        // Pin message
        $response = $this->actingAs($user1)->postJson("/api/messages/{$message->id}/pin");
        $response->assertStatus(200)
            ->assertJson([
                'is_pinned' => true,
            ]);

        $this->assertTrue((bool) $message->fresh()->is_pinned);
        $this->assertNotNull($message->fresh()->pinned_at);

        // Unpin message
        $response2 = $this->actingAs($user1)->postJson("/api/messages/{$message->id}/pin");
        $response2->assertStatus(200)
            ->assertJson([
                'is_pinned' => false,
            ]);

        $this->assertFalse((bool) $message->fresh()->is_pinned);
    }

    public function test_user_can_search_messages_in_conversation(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        $conversation = Conversation::create(['type' => 'direct']);
        $conversation->users()->attach([$user1->id, $user2->id]);

        Message::create([
            'conversation_id' => $conversation->id,
            'sender_id' => $user1->id,
            'body' => 'La clave secreta es Omega777',
        ]);

        Message::create([
            'conversation_id' => $conversation->id,
            'sender_id' => $user2->id,
            'body' => 'Entendido, recibida la señal',
        ]);

        $response = $this->actingAs($user1)->getJson("/api/conversations/{$conversation->id}/search?q=Omega777");

        $response->assertStatus(200)
            ->assertJsonCount(1, 'messages')
            ->assertJsonPath('messages.0.body', 'La clave secreta es Omega777');
    }
}
