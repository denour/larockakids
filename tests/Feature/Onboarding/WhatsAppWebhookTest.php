<?php

use App\Models\OnboardingSession;

beforeEach(function () {
    config()->set('onboarding.webhook_verify_token', 'secret-verify');
});

test('the webhook verification returns the challenge for the correct token', function () {
    $this->get('/webhooks/whatsapp?hub_mode=subscribe&hub_verify_token=secret-verify&hub_challenge=42')
        ->assertOk()
        ->assertSee('42');
});

test('the webhook verification rejects a wrong token', function () {
    $this->get('/webhooks/whatsapp?hub_mode=subscribe&hub_verify_token=nope&hub_challenge=42')
        ->assertStatus(403);
});

test('an inbound code from a registered guardian matches the session to the kid', function () {
    ['kid' => $kid] = createKidWithContact(
        [],
        ['phone' => '6641234567', 'international_code' => '52'],
    );
    $session = OnboardingSession::factory()->create(['code' => '654321']);

    $this->postJson('/webhooks/whatsapp', metaMessage('5216641234567', '654321'))
        ->assertOk();

    $session->refresh();
    expect($session->status)->toBe('matched')
        ->and($session->kid_id)->toBe($kid->id);
});

test('an inbound code from an unknown phone leaves the session pending', function () {
    createKidWithContact([], ['phone' => '6641234567', 'international_code' => '52']);
    $session = OnboardingSession::factory()->create(['code' => '654321']);

    $this->postJson('/webhooks/whatsapp', metaMessage('5219990000000', '654321'))->assertOk();

    expect($session->refresh()->status)->toBe('pending');
});

test('an inbound message with an unknown code matches nothing', function () {
    createKidWithContact([], ['phone' => '6641234567', 'international_code' => '52']);
    $session = OnboardingSession::factory()->create(['code' => '654321']);

    $this->postJson('/webhooks/whatsapp', metaMessage('5216641234567', '111111'))->assertOk();

    expect($session->refresh()->status)->toBe('pending');
});

test('an expired session is never matched', function () {
    createKidWithContact([], ['phone' => '6641234567', 'international_code' => '52']);
    $session = OnboardingSession::factory()->expired()->create(['code' => '654321']);

    $this->postJson('/webhooks/whatsapp', metaMessage('5216641234567', '654321'))->assertOk();

    expect($session->refresh()->status)->toBe('pending');
});

/**
 * Build a minimal Meta WhatsApp Cloud inbound-message webhook payload.
 */
function metaMessage(string $from, string $body): array
{
    return [
        'entry' => [[
            'changes' => [[
                'value' => [
                    'messages' => [[
                        'from' => $from,
                        'type' => 'text',
                        'text' => ['body' => $body],
                    ]],
                ],
            ]],
        ]],
    ];
}
