<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AccountRecoveryTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_register_with_two_security_questions(): void
    {
        $response = $this->post('/register', [
            'username' => 'agente_secreto',
            'display_name' => 'Agente Secreto',
            'password' => 'claveSecreta123',
            'password_confirmation' => 'claveSecreta123',
            'security_question_1' => '¿Cuál es el nombre de tu primera mascota?',
            'security_answer_1' => 'Firulais',
            'security_question_2' => '¿En qué ciudad o pueblo naciste?',
            'security_answer_2' => 'Madrid',
        ]);

        $response->assertRedirect('/chat');
        $this->assertAuthenticated();

        $user = User::where('username', 'agente_secreto')->first();
        $this->assertNotNull($user);
        $this->assertEquals('¿Cuál es el nombre de tu primera mascota?', $user->security_question_1);
        $this->assertEquals('¿En qué ciudad o pueblo naciste?', $user->security_question_2);
        $this->assertTrue(Hash::check('firulais', $user->security_answer_1));
        $this->assertTrue(Hash::check('madrid', $user->security_answer_2));
    }

    public function test_user_cannot_register_with_same_security_questions(): void
    {
        $response = $this->post('/register', [
            'username' => 'agente_duplicado',
            'password' => 'claveSecreta123',
            'password_confirmation' => 'claveSecreta123',
            'security_question_1' => '¿Cuál es el nombre de tu primera mascota?',
            'security_answer_1' => 'Firulais',
            'security_question_2' => '¿Cuál es el nombre de tu primera mascota?',
            'security_answer_2' => 'Rex',
        ]);

        $response->assertSessionHasErrors('security_question_2');
    }

    public function test_user_can_fetch_security_questions_by_username(): void
    {
        User::create([
            'username' => 'sombra_x',
            'password' => Hash::make('password123'),
            'security_question_1' => '¿Cuál es el nombre de tu primera mascota?',
            'security_answer_1' => Hash::make('pelusa'),
            'security_question_2' => '¿Cuál fue tu primer videojuego o consola?',
            'security_answer_2' => 'Zelda',
        ]);

        $response = $this->postJson('/recovery/questions', [
            'username' => 'sombra_x',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'username' => 'sombra_x',
                'question_1' => '¿Cuál es el nombre de tu primera mascota?',
                'question_2' => '¿Cuál fue tu primer videojuego o consola?',
            ]);

        // Asegurar que las respuestas NUNCA se exponen en la respuesta JSON
        $this->assertArrayNotHasKey('security_answer_1', $response->json());
        $this->assertArrayNotHasKey('security_answer_2', $response->json());
    }

    public function test_cannot_fetch_questions_for_nonexistent_or_unconfigured_user(): void
    {
        $response = $this->postJson('/recovery/questions', [
            'username' => 'usuario_inexistente',
        ]);

        $response->assertStatus(404);

        // Usuario sin preguntas configuradas
        User::create([
            'username' => 'sin_preguntas',
            'password' => Hash::make('password123'),
        ]);

        $response2 = $this->postJson('/recovery/questions', [
            'username' => 'sin_preguntas',
        ]);

        $response2->assertStatus(404);
    }

    public function test_user_can_reset_password_with_correct_answers(): void
    {
        $user = User::create([
            'username' => 'ciber_fantasma',
            'password' => Hash::make('claveAntigua123'),
            'security_question_1' => '¿Cuál es tu comida, postre o plato favorito?',
            'security_answer_1' => Hash::make('pizza napolitana'),
            'security_question_2' => '¿En qué ciudad o pueblo naciste?',
            'security_answer_2' => Hash::make('valencia'),
        ]);

        $response = $this->postJson('/recovery/reset', [
            'username' => 'ciber_fantasma',
            'answer_1' => 'Pizza Napolitana', // Comprobar insensibilidad a mayúsculas
            'answer_2' => '  valencia  ',    // Comprobar auto-trim
            'password' => 'nuevaClaveSegura2026',
            'password_confirmation' => 'nuevaClaveSegura2026',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('user.username', 'ciber_fantasma');

        $this->assertAuthenticatedAs($user);

        $user->refresh();
        $this->assertTrue(Hash::check('nuevaClaveSegura2026', $user->password));
    }

    public function test_user_cannot_reset_password_with_wrong_answers(): void
    {
        $user = User::create([
            'username' => 'ciber_fantasma',
            'password' => Hash::make('claveAntigua123'),
            'security_question_1' => '¿Cuál es tu comida, postre o plato favorito?',
            'security_answer_1' => Hash::make('pizza napolitana'),
            'security_question_2' => '¿En qué ciudad o pueblo naciste?',
            'security_answer_2' => Hash::make('valencia'),
        ]);

        $response = $this->postJson('/recovery/reset', [
            'username' => 'ciber_fantasma',
            'answer_1' => 'Hamburguesa', // Incorrecta
            'answer_2' => 'valencia',
            'password' => 'nuevaClaveSegura2026',
            'password_confirmation' => 'nuevaClaveSegura2026',
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('message', 'Una o ambas respuestas de seguridad son incorrectas.');

        $user->refresh();
        $this->assertTrue(Hash::check('claveAntigua123', $user->password));
    }
}
