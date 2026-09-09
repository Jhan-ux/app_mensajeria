<?php

namespace Tests\Feature;

use App\Models\Conversation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GroupManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_add_member_to_group(): void
    {
        $admin = User::factory()->create();
        $newMember = User::factory()->create(['username' => 'nuevo_agente']);

        $group = Conversation::create([
            'type' => 'group',
            'title' => 'Grupo Secreto',
            'created_by' => $admin->id,
        ]);
        $group->users()->attach($admin->id, ['role' => 'admin']);

        $response = $this->actingAs($admin)->postJson("/api/conversations/{$group->id}/members", [
            'username' => 'nuevo_agente',
        ]);

        $response->assertStatus(200);
        $this->assertTrue($group->users()->where('users.id', $newMember->id)->exists());
    }

    public function test_admin_can_kick_member_from_group(): void
    {
        $admin = User::factory()->create();
        $member = User::factory()->create();

        $group = Conversation::create([
            'type' => 'group',
            'title' => 'Grupo Alfa',
            'created_by' => $admin->id,
        ]);
        $group->users()->attach([
            $admin->id => ['role' => 'admin'],
            $member->id => ['role' => 'member'],
        ]);

        $response = $this->actingAs($admin)->deleteJson("/api/conversations/{$group->id}/members/{$member->id}");

        $response->assertStatus(200);
        $this->assertFalse($group->users()->where('users.id', $member->id)->exists());
    }

    public function test_non_admin_cannot_kick_member(): void
    {
        $member1 = User::factory()->create();
        $member2 = User::factory()->create();

        $group = Conversation::create(['type' => 'group', 'title' => 'Grupo']);
        $group->users()->attach([
            $member1->id => ['role' => 'member'],
            $member2->id => ['role' => 'member'],
        ]);

        $response = $this->actingAs($member1)->deleteJson("/api/conversations/{$group->id}/members/{$member2->id}");

        $response->assertStatus(403);
    }

    public function test_member_can_leave_group(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        $group = Conversation::create(['type' => 'group', 'title' => 'Grupo']);
        $group->users()->attach([
            $user1->id => ['role' => 'admin'],
            $user2->id => ['role' => 'member'],
        ]);

        $response = $this->actingAs($user2)->postJson("/api/conversations/{$group->id}/leave");

        $response->assertStatus(200);
        $this->assertFalse($group->users()->where('users.id', $user2->id)->exists());
    }

    public function test_can_update_group_info(): void
    {
        $admin = User::factory()->create();

        $group = Conversation::create([
            'type' => 'group',
            'title' => 'Nombre Viejo',
            'description' => 'Descripcion vieja',
        ]);
        $group->users()->attach($admin->id, ['role' => 'admin']);

        $response = $this->actingAs($admin)->putJson("/api/conversations/{$group->id}/info", [
            'title' => 'Nuevo Nombre Grupo',
            'description' => 'Nueva descripción segura',
        ]);

        $response->assertStatus(200);
        $this->assertEquals('Nuevo Nombre Grupo', $group->fresh()->title);
        $this->assertEquals('Nueva descripción segura', $group->fresh()->description);
    }
}
