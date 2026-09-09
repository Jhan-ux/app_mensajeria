<?php

namespace Tests\Feature;

use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EphemeralAndSecurityFeaturesTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_set_ephemeral_timer_on_conversation(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        $conversation = Conversation::create(['type' => 'direct']);
        $conversation->users()->attach([$user1->id, $user2->id]);

        $response = $this->actingAs($user1)->postJson("/api/conversations/{$conversation->id}/ephemeral", [
            'timer' => 3600, // 1 hour
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'ephemeral_timer' => 3600,
            ]);

        $this->assertEquals(3600, $conversation->fresh()->ephemeral_timer);
    }

    public function test_ephemeral_message_sets_expiration_time(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        $conversation = Conversation::create([
            'type' => 'direct',
            'ephemeral_timer' => 60, // 60 seconds
        ]);
        $conversation->users()->attach([$user1->id, $user2->id]);

        $response = $this->actingAs($user1)->postJson("/api/conversations/{$conversation->id}/messages", [
            'body' => 'Este mensaje se autodestruirá en 60 segundos',
        ]);

        $response->assertStatus(201);
        $message = Message::first();
        $this->assertNotNull($message->expires_at);
        $this->assertTrue($message->expires_at->isFuture());
    }

    public function test_view_once_message_can_be_consumed(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        $conversation = Conversation::create(['type' => 'direct']);
        $conversation->users()->attach([$user1->id, $user2->id]);

        $message = Message::create([
            'conversation_id' => $conversation->id,
            'sender_id' => $user1->id,
            'body' => 'Contenido secreto vista única',
            'view_once' => true,
        ]);

        // User 2 consumes the view once message
        $response = $this->actingAs($user2)->postJson("/api/messages/{$message->id}/view-once");

        $response->assertStatus(200)
            ->assertJson([
                'body' => 'Contenido secreto vista única',
            ]);

        $this->assertNotNull($message->fresh()->viewed_at);

        // Second attempt to view should fail with 410 Gone
        $secondResponse = $this->actingAs($user2)->postJson("/api/messages/{$message->id}/view-once");
        $secondResponse->assertStatus(410);
    }

    public function test_user_can_permanently_destroy_account(): void
    {
        $user = User::factory()->create();

        $conversation = Conversation::create(['type' => 'direct']);
        $conversation->users()->attach([$user->id]);

        Message::create([
            'conversation_id' => $conversation->id,
            'sender_id' => $user->id,
            'body' => 'Mensaje que desaparecerá',
        ]);

        $response = $this->actingAs($user)->deleteJson('/api/account/destroy', [
            'password' => 'password123',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure(['message', 'redirect']);

        $this->assertDatabaseMissing('users', ['id' => $user->id]);
        $this->assertDatabaseMissing('messages', ['sender_id' => $user->id]);
    }
}
