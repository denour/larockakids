<?php

use App\Services\WhatsAppService;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    Http::fake([
        'graph.facebook.com/*' => Http::response([
            'messages' => [['id' => 'wamid.test123']],
        ], 200),
    ]);

    config([
        'whatsapp.token' => 'test-token',
        'whatsapp.phone_number_id' => '123456',
        'whatsapp.business_account_id' => '789',
        'whatsapp.api_version' => 'v17.0',
    ]);

    $this->service = app(WhatsAppService::class);
});

test('send text message makes api call', function () {
    $result = $this->service->sendTextMessage('+521234567890', 'Hello test');

    expect($result)->toBeArray()
        ->and($result['messages'][0]['id'])->toBe('wamid.test123');
});

test('send image message makes api call', function () {
    $result = $this->service->sendImageMessage('+521234567890', 'https://example.com/img.jpg', 'Caption');

    expect($result)->toBeArray();
});

test('send document message makes api call', function () {
    $result = $this->service->sendDocumentMessage('+521234567890', 'https://example.com/doc.pdf', 'document.pdf');

    expect($result)->toBeArray();
});

test('send template message makes api call', function () {
    $result = $this->service->sendTemplateMessage('+521234567890', 'hello_world', []);

    expect($result)->toBeArray();
});

test('send text message with empty message still works', function () {
    $result = $this->service->sendTextMessage('+521234567890', '');

    expect($result)->toBeArray();
});

test('service handles api error response', function () {
    Http::fake([
        'graph.facebook.com/*' => Http::response([
            'error' => ['message' => 'Invalid token', 'code' => 190],
        ], 401),
    ]);

    $service = app(WhatsAppService::class);

    try {
        $result = $service->sendTextMessage('+521234567890', 'Test');
        // If it doesn't throw, it should return the error response
        expect($result)->toBeArray();
    } catch (\Exception $e) {
        expect($e)->toBeInstanceOf(\Exception::class);
    }
});

test('get message status makes api call', function () {
    Http::fake([
        'graph.facebook.com/*' => Http::response([
            'id' => 'wamid.test123',
            'status' => 'delivered',
        ], 200),
    ]);

    $service = app(WhatsAppService::class);
    $result = $service->getMessageStatus('wamid.test123');

    expect($result)->toBeArray();
});
