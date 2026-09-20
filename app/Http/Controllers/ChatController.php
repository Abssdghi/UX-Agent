<?php

namespace App\Http\Controllers;

use App\Services\UXAgent;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use RuntimeException;

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
     * Accept a user message, ask the agent for the next question, return it.
     */
    public function send(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'message' => ['required', 'string', 'max:20000'],
        ]);

        $history = $this->history($request);
        $history[] = [
            'role' => 'user',
            'content' => $validated['message'],
        ];

        try {
            $reply = $this->agent->reply($history);
        } catch (RuntimeException $e) {
            return response()->json([
                'ok' => false,
                'error' => $e->getMessage(),
            ], 502);
        }

        $history[] = [
            'role' => 'assistant',
            'content' => $reply,
        ];

        $request->session()->put(self::SESSION_KEY, $history);

        return response()->json([
            'ok' => true,
            'reply' => $reply,
        ]);
    }

    /**
     * Ask the agent to produce the final structured document.
     */
    public function finalize(Request $request): JsonResponse
    {
        $history = $this->history($request);

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

        return response()->json([
            'ok' => true,
            'document' => $document,
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
     * @return array<int, array{role: string, content: string}>
     */
    private function history(Request $request): array
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