<?php

namespace Tests\Feature;

use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

class SecurityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        RateLimiter::clear('login');
        RateLimiter::clear('register');
        RateLimiter::clear('messages');
        RateLimiter::clear('search');
    }

    public function test_security_headers_are_present_in_responses(): void
    {
        $response = $this->get('/login');

        $response->assertStatus(200);
        $response->assertHeader('X-Frame-Options', 'SAMEORIGIN');
        $response->assertHeader('X-Content-Type-Options', 'nosniff');
        $response->assertHeader('X-XSS-Protection', '1; mode=block');
        $response->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->assertHeader('Permissions-Policy', 'camera=(), microphone=(self), geolocation=()');
    }

    public function test_message_body_is_stored_encrypted_in_database(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        $conversation = Conversation::create(['type' => 'direct']);
        $conversation->users()->attach([$user1->id, $user2->id]);

        $plainSecret = 'Mensaje Ultra Secreto Confidencial 9876';

        $message = Message::create([
            'conversation_id' => $conversation->id,
            'sender_id' => $user1->id,
            'type' => 'text',
            'body' => $plainSecret,
        ]);

        // Model automatically decrypts
        $this->assertEquals($plainSecret, $message->body);

        // Direct raw DB check to verify raw text is NOT stored in plain text
        $rawMessage = DB::table('messages')->where('id', $message->id)->first();
        $this->assertNotEquals($plainSecret, $rawMessage->body);
        $this->assertStringNotContainsString('Mensaje Ultra Secreto', $rawMessage->body);
    }

    public function test_brute_force_login_rate_limiting_triggers_after_limit(): void
    {
        $user = User::create([
            'username' => 'agent_smith',
            'password' => Hash::make('correct_password'),
        ]);

        // 5 failed attempts allowed
        for ($i = 0; $i < 5; $i++) {
            $response = $this->postJson('/login', [
                'username' => 'agent_smith',
                'password' => 'wrong_password',
            ]);
            $response->assertStatus(422);
        }

        // 6th attempt should be blocked by rate limiter with 429 Too Many Requests
        $blockedResponse = $this->postJson('/login', [
            'username' => 'agent_smith',
            'password' => 'wrong_password',
        ]);

        $blockedResponse->assertStatus(429);
    }

    public function test_user_cannot_access_or_send_messages_to_other_users_conversation(): void
    {
        $alice = User::factory()->create(['username' => 'alice']);
        $bob = User::factory()->create(['username' => 'bob']);
        $intruder = User::factory()->create(['username' => 'intruder']);

        $privateChat = Conversation::create(['type' => 'direct']);
        $privateChat->users()->attach([$alice->id, $bob->id]);

        // Intruder tries to get messages from privateChat
        $response = $this->actingAs($intruder)->getJson("/api/conversations/{$privateChat->id}/messages");
        $response->assertStatus(404);

        // Intruder tries to post a message into privateChat
        $postResponse = $this->actingAs($intruder)->postJson("/api/conversations/{$privateChat->id}/messages", [
            'body' => 'I am eavesdropping',
        ]);
        $postResponse->assertStatus(404);
    }

    public function test_file_upload_rejects_dangerous_executable_and_script_extensions(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        $conversation = Conversation::create(['type' => 'direct']);
        $conversation->users()->attach([$user1->id, $user2->id]);

        // Try uploading a malicious php shell file
        $maliciousFile = UploadedFile::fake()->create('exploit.php', 10, 'application/x-php');

        $response = $this->actingAs($user1)->postJson("/api/conversations/{$conversation->id}/messages", [
            'file' => $maliciousFile,
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['file']);
    }

    public function test_file_upload_accepts_valid_image_and_document_extensions(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        $conversation = Conversation::create(['type' => 'direct']);
        $conversation->users()->attach([$user1->id, $user2->id]);

        // Try uploading a valid png file
        $validImage = UploadedFile::fake()->create('avatar_secure.png', 50, 'image/png');

        $response = $this->actingAs($user1)->postJson("/api/conversations/{$conversation->id}/messages", [
            'file' => $validImage,
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('messages', [
            'conversation_id' => $conversation->id,
            'type' => 'image',
        ]);
    }
}
