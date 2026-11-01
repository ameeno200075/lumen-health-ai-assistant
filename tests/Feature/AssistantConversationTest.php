<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AssistantConversationTest extends TestCase
{
    use RefreshDatabase;

    public function test_users_cannot_read_another_users_conversation(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();
        $conversationId = (string) str()->uuid();

        DB::table('agent_conversations')->insert([
            'id' => $conversationId,
            'participant_type' => User::class,
            'participant_id' => $owner->id,
            'title' => 'Private conversation',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($otherUser)
            ->getJson(route('assistant.conversation', $conversationId))
            ->assertNotFound();
    }

    public function test_users_can_read_their_own_conversation_messages(): void
    {
        $owner = User::factory()->create();
        $conversationId = (string) str()->uuid();

        DB::table('agent_conversations')->insert([
            'id' => $conversationId,
            'participant_type' => User::class,
            'participant_id' => $owner->id,
            'title' => 'My conversation',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('agent_conversation_messages')->insert([
            'id' => (string) str()->uuid(),
            'conversation_id' => $conversationId,
            'participant_type' => User::class,
            'participant_id' => $owner->id,
            'agent' => 'appointment-assistant',
            'role' => 'assistant',
            'content' => 'How can I help?',
            'attachments' => '[]',
            'steps' => '[]',
            'usage' => '{}',
            'meta' => '{}',
            'status' => 'completed',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($owner)
            ->getJson(route('assistant.conversation', $conversationId))
            ->assertOk()
            ->assertJsonPath('messages.0.content', 'How can I help?');
    }
}
