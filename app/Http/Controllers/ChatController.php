<?php

namespace App\Http\Controllers;

use App\Models\Conversation;
use App\Models\Message;
use App\Services\UXAgent;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use RuntimeException;
use Throwable;

class ChatController extends Controller
{
    private const SESSION_KEY = 'ux_agent.history';

    public function __construct(private readonly UXAgent $agent)
    {
    }

    /**
     * Render the single-page UI.
     */
    public function index(): View
    {
        return view('chat');
    }

    /**
     * List every stored conversation with its own cost.
     */
    public function history(): JsonResponse
    {
        try {
            $conversations = Conversation::with('messages')->latest('updated_at')->get();
        } catch (Throwable) {
            return response()->json(['ok' => false]);
        }

        $chats = $conversations->map(function (Conversation $conversation) {
            return $this->present($conversation) + [
                'messages' => $conversation->messages->map(fn (Message $message) => [
                    'role' => $message->role,
                    'content' => $message->content,
                    'at' => $message->created_at?->toIso8601String(),
                ])->all(),
            ];
        })->all();

        return response()->json([
            'ok' => true,
            'chats' => $chats,
        ]);
    }

    /**
     * Accept a user message, ask the agent for the next question, return it.
     */
    public function send(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'message' => ['required', 'string', 'max:20000'],
            'conversation_id' => ['nullable', 'integer'],
        ]);

        $message = $validated['message'];
        $conversation = null;

        try {
            $conversation = $this->conversation($validated['conversation_id'] ?? null);
        } catch (Throwable) {
            $conversation = null;
        }

        if ($conversation === null) {
            $history = $this->sessionHistory($request);
            $history[] = ['role' => 'user', 'content' => $message];
        } else {
            try {
                $conversation->messages()->create(['role' => 'user', 'content' => $message]);
                $history = $this->agentHistory($conversation);
            } catch (Throwable) {
                $conversation = null;
                $history = $this->sessionHistory($request);
                $history[] = ['role' => 'user', 'content' => $message];
            }
        }

        try {
            $reply = $this->agent->reply($history);
        } catch (RuntimeException $e) {
            return response()->json([
                'ok' => false,
                'error' => $e->getMessage(),
            ], 502);
        }

        if ($conversation === null) {
            $history[] = ['role' => 'assistant', 'content' => $reply];
            $request->session()->put(self::SESSION_KEY, $history);

            return response()->json([
                'ok' => true,
                'reply' => $reply,
                'cost' => $this->agent->cost(),
            ]);
        }

        try {
            $conversation->messages()->create(['role' => 'assistant', 'content' => $reply]);

            if ($conversation->title === null) {
                $conversation->title = mb_substr(trim($message), 0, 80);
            }
        } catch (Throwable) {
            return response()->json([
                'ok' => true,
                'reply' => $reply,
                'cost' => $this->agent->cost(),
                'conversation' => null,
            ]);
        }

        $stored = $this->recordCost($conversation);

        return response()->json([
            'ok' => true,
            'reply' => $reply,
            'cost' => $this->agent->cost(),
            'conversation' => $stored ? $this->present($conversation) : null,
        ]);
    }

    /**
     * Ask the agent to produce the final structured document.
     */
    public function finalize(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'conversation_id' => ['nullable', 'integer'],
        ]);

        $conversation = null;

        try {
            $id = $validated['conversation_id'] ?? null;
            $conversation = $id === null ? null : Conversation::find($id);
        } catch (Throwable) {
            $conversation = null;
        }

        $history = $conversation === null
            ? $this->sessionHistory($request)
            : $this->agentHistory($conversation);

        if (count($history) === 0) {
            return response()->json([
                'ok' => false,
                'error' => 'Nothing to finalize. Start the conversation first.',
            ], 422);
        }

        try {
            $document = $this->agent->finalize($history);
        } catch (RuntimeException $e) {
            return response()->json([
                'ok' => false,
                'error' => $e->getMessage(),
            ], 502);
        }

        $stored = $this->recordCost($conversation);

        return response()->json([
            'ok' => true,
            'document' => $document,
            'cost' => $this->agent->cost(),
            'conversation' => $stored ? $this->present($conversation) : null,
        ]);
    }

    /**
     * Clear the conversation history.
     */
    public function reset(Request $request): JsonResponse
    {
        $request->session()->forget(self::SESSION_KEY);

        return response()->json(['ok' => true]);
    }

    /**
     * Find the requested conversation or start a new one.
     */
    private function conversation(?int $id): Conversation
    {
        if ($id !== null) {
            $conversation = Conversation::find($id);
            if ($conversation !== null) {
                return $conversation;
            }
        }

        return Conversation::create();
    }

    /**
     * Add the cost of the last call to the conversation total.
     */
    private function recordCost(?Conversation $conversation): bool
    {
        if ($conversation === null) {
            return false;
        }

        try {
            $cost = $this->agent->cost();
            $conversation->cost = (float) $conversation->cost + (is_numeric($cost) ? (float) $cost : 0.0);
            $conversation->updated_at = now();
            $conversation->save();

            return true;
        } catch (Throwable) {
            return false;
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function present(Conversation $conversation): array
    {
        return [
            'id' => $conversation->id,
            'title' => $conversation->title,
            'cost' => (float) $conversation->cost,
            'updated_at' => $conversation->updated_at?->toIso8601String(),
        ];
    }

    /**
     * @return array<int, array{role: string, content: string}>
     */
    private function agentHistory(Conversation $conversation): array
    {
        return $conversation->messages()
            ->orderBy('id')
            ->get(['role', 'content'])
            ->map(fn (Message $message) => [
                'role' => $message->role,
                'content' => $message->content,
            ])
            ->all();
    }

    /**
     * @return array<int, array{role: string, content: string}>
     */
    private function sessionHistory(Request $request): array
    {
        $history = $request->session()->get(self::SESSION_KEY, []);

        if (!is_array($history)) {
            return [];
        }

        $clean = [];
        foreach ($history as $entry) {
            if (
                is_array($entry)
                && isset($entry['role'], $entry['content'])
                && is_string($entry['role'])
                && is_string($entry['content'])
                && in_array($entry['role'], ['user', 'assistant'], true)
            ) {
                $clean[] = [
                    'role' => $entry['role'],
                    'content' => $entry['content'],
                ];
            }
        }

        return $clean;
    }
}