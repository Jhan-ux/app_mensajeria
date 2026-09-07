<?php

namespace Database\Seeders;

use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $password = Hash::make('password123');

        // 1. Crear usuarios anónimos
        $sombra = User::create([
            'username' => 'sombra',
            'display_name' => 'Sombra Nocturna',
            'password' => $password,
            'avatar' => 'https://api.dicebear.com/7.x/bottts-neutral/svg?seed=sombra',
            'status_message' => 'Invisible en la red',
            'is_online' => true,
            'last_seen_at' => now(),
        ]);

        $neo = User::create([
            'username' => 'neo',
            'display_name' => 'Neo',
            'password' => $password,
            'avatar' => 'https://api.dicebear.com/7.x/bottts-neutral/svg?seed=neo',
            'status_message' => 'Despertando de la Matrix',
            'is_online' => true,
            'last_seen_at' => now(),
        ]);

        $trinity = User::create([
            'username' => 'trinity',
            'display_name' => 'Trinity',
            'password' => $password,
            'avatar' => 'https://api.dicebear.com/7.x/bottts-neutral/svg?seed=trinity',
            'status_message' => 'La respuesta está ahí fuera',
            'is_online' => true,
            'last_seen_at' => now(),
        ]);

        $agente007 = User::create([
            'username' => 'agente007',
            'display_name' => 'Agente Secreto',
            'password' => $password,
            'avatar' => 'https://api.dicebear.com/7.x/bottts-neutral/svg?seed=agente007',
            'status_message' => 'Canal encriptado activado',
            'is_online' => false,
            'last_seen_at' => now()->subMinutes(25),
        ]);

        $cypher = User::create([
            'username' => 'cypher',
            'display_name' => 'Cypher',
            'password' => $password,
            'avatar' => 'https://api.dicebear.com/7.x/bottts-neutral/svg?seed=cypher',
            'status_message' => 'La ignorancia es una bendición',
            'is_online' => false,
            'last_seen_at' => now()->subHours(2),
        ]);

        // 2. Chat Directo: Neo & Trinity
        $directChat = Conversation::create([
            'type' => 'direct',
            'created_by' => $trinity->id,
        ]);

        $directChat->users()->attach([
            $neo->id => ['role' => 'member'],
            $trinity->id => ['role' => 'member'],
        ]);

        Message::create([
            'conversation_id' => $directChat->id,
            'sender_id' => $trinity->id,
            'type' => 'text',
            'body' => 'Neo, sé por qué estás aquí. Sé lo que has estado haciendo...',
            'created_at' => now()->subMinutes(15),
        ]);

        Message::create([
            'conversation_id' => $directChat->id,
            'sender_id' => $neo->id,
            'type' => 'text',
            'body' => '¿Quién eres? ¿Cómo encontraste este canal anónimo?',
            'created_at' => now()->subMinutes(12),
        ]);

        $lastMsg1 = Message::create([
            'conversation_id' => $directChat->id,
            'sender_id' => $trinity->id,
            'type' => 'text',
            'body' => 'Sigue al conejo blanco. Nos vemos en el canal seguro.',
            'created_at' => now()->subMinutes(5),
        ]);

        // 3. Grupo: Resistencia Anónima
        $groupChat = Conversation::create([
            'type' => 'group',
            'title' => 'Resistencia Anónima',
            'avatar' => 'https://api.dicebear.com/7.x/identicon/svg?seed=resistencia',
            'created_by' => $neo->id,
        ]);

        $groupChat->users()->attach([
            $neo->id => ['role' => 'admin'],
            $trinity->id => ['role' => 'member'],
            $sombra->id => ['role' => 'member'],
            $agente007->id => ['role' => 'member'],
        ]);

        Message::create([
            'conversation_id' => $groupChat->id,
            'sender_id' => $sombra->id,
            'type' => 'text',
            'body' => 'Bienvenidos al grupo anónimo. Todos los mensajes viajan en tiempo real.',
            'created_at' => now()->subMinutes(30),
        ]);

        Message::create([
            'conversation_id' => $groupChat->id,
            'sender_id' => $neo->id,
            'type' => 'text',
            'body' => 'Confirmado. El canal es 100% privado y no requiere correos ni datos personales.',
            'created_at' => now()->subMinutes(2),
        ]);
    }
}
