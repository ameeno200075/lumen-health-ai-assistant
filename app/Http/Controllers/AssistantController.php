<?php

namespace App\Http\Controllers;

use App\Ai\Agents\AppointmentAssistant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;
use Throwable;

class AssistantController extends Controller
{
    /**
     * Display the assistant page and the authenticated user's conversations.
     */
    public function index(Request $request): Response
    {
        $user = $request->user();

        $conversations = DB::table('agent_conversations')
            ->where('participant_type', $user::class)
            ->where('participant_id', $user->id)
            ->orderByDesc('updated_at')
            ->limit(30)
            ->get(['id', 'title', 'updated_at']);

        return Inertia::render('assistant/index', [
            'conversations' => $conversations,
        ]);
    }

    /**
     * Return the messages belonging to one conversation.
     */
    public function show(Request $request, string $conversation): JsonResponse
    {
        $user = $request->user();

        // Make sure the conversation belongs to the authenticated user.
        $ownsConversation = DB::table('agent_conversations')
            ->where('id', $conversation)
            ->where('participant_type', $user::class)
            ->where('participant_id', $user->id)
            ->exists();

        abort_unless($ownsConversation, 404);

        // Load messages from the conversation's message table.
        $messages = DB::table('agent_conversation_messages')
            ->where('conversation_id', $conversation)
            ->whereIn('role', ['user', 'assistant'])
            ->whereNotNull('content')
            ->whereRaw("TRIM(content) <> ''")
            ->orderBy('id')
            ->get(['id', 'role', 'content'])
            ->map(fn ($message) => [
                'id' => $message->id,
                'role' => $message->role,
                'content' => $message->content,
            ])
            ->values();

        return response()->json([
            'conversation_id' => $conversation,
            'messages' => $messages,
        ]);
    }

    /**
     * Send a message to the AI assistant.
     */
    public function message(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'message' => ['required', 'string', 'max:2000'],
            'conversation_id' => ['nullable', 'string', 'uuid'],
        ]);

        $user = $request->user();

        // Continue an existing conversation only if it belongs to this user.
        if (!empty($validated['conversation_id'])) {
            $ownsConversation = DB::table('agent_conversations')
                ->where('id', $validated['conversation_id'])
                ->where('participant_type', $user::class)
                ->where('participant_id', $user->id)
                ->exists();

            abort_unless($ownsConversation, 404);

            $assistant = (new AppointmentAssistant)->continue(
                $validated['conversation_id'],
                as: $user
            );
        } else {
            // Associate a new conversation with the authenticated user.
            $assistant = (new AppointmentAssistant)->forUser($user);
        }

        try {
            $response = $assistant->prompt($validated['message']);
        } catch (Throwable $exception) {
            report($exception);

            return response()->json([
                'message' => 'The assistant is temporarily unavailable. Please try again.',
            ], 503);
        }

        return response()->json([
            'response' => (string) $response,
            'conversation_id' => $response->conversationId,
        ]);
    }
}
