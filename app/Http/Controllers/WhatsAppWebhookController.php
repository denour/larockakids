<?php

namespace App\Http\Controllers;

use App\Services\OnboardingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class WhatsAppWebhookController extends Controller
{
    public function __construct(private readonly OnboardingService $onboarding) {}

    /**
     * Meta webhook verification handshake.
     */
    public function verify(Request $request): Response
    {
        $verifyToken = config('onboarding.webhook_verify_token');

        if (
            $request->query('hub_mode') === 'subscribe'
            && $verifyToken !== null
            && $request->query('hub_verify_token') === $verifyToken
        ) {
            return response((string) $request->query('hub_challenge'), 200)
                ->header('Content-Type', 'text/plain');
        }

        return response('Forbidden', 403);
    }

    /**
     * Inbound message handler — match code + sender phone to a pending session.
     */
    public function handle(Request $request): JsonResponse
    {
        foreach ($this->extractMessages($request) as $message) {
            $from = $message['from'] ?? null;
            $body = $message['text']['body'] ?? null;

            if (is_string($from) && is_string($body)) {
                $this->onboarding->matchInboundMessage($body, $from);
            }
        }

        return response()->json(['received' => true]);
    }

    /**
     * Pull the list of inbound message payloads out of the Meta webhook body.
     *
     * @return array<int, array<string, mixed>>
     */
    private function extractMessages(Request $request): array
    {
        $messages = [];

        foreach ((array) $request->input('entry', []) as $entry) {
            foreach ((array) ($entry['changes'] ?? []) as $change) {
                foreach ((array) ($change['value']['messages'] ?? []) as $message) {
                    $messages[] = $message;
                }
            }
        }

        return $messages;
    }
}
