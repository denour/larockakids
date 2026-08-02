<?php

use App\Events\WhatsAppNotification;
use App\Services\WhatsAppBridgeService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

beforeEach(function () {
    config()->set('whatsapp.bridge', [
        'url' => 'https://bridge.test',
        'api_key' => 'test-key',
        'instance' => 'piedritas-test',
        'timeout' => 5,
    ]);

    // El evento sigue siendo ShouldBroadcast; sin esto el test intentaría hablarle
    // a Pusher de verdad. La entrega del aviso ya no depende del broadcast.
    config()->set('broadcasting.default', 'null');
});

describe('WhatsAppBridgeService', function () {
    it('envía el texto al endpoint de la instancia con la api key', function () {
        Http::fake(['bridge.test/*' => Http::response(['key' => ['id' => 'X']], 201)]);

        $ok = app(WhatsAppBridgeService::class)->sendText('525551234567', 'Hola mundo');

        expect($ok)->toBeTrue();
        Http::assertSent(function ($request) {
            return $request->url() === 'https://bridge.test/message/sendText/piedritas-test'
                && $request->hasHeader('apikey', 'test-key')
                && $request['number'] === '525551234567'
                && $request['text'] === 'Hola mundo';
        });
    });

    it('recorta la diagonal final de la url para no generar doble slash', function () {
        config()->set('whatsapp.bridge.url', 'https://bridge.test/');
        Http::fake(['bridge.test/*' => Http::response([], 201)]);

        app(WhatsAppBridgeService::class)->sendText('525551234567', 'Hola');

        Http::assertSent(fn ($request) => $request->url() === 'https://bridge.test/message/sendText/piedritas-test');
    });

    it('no envía nada y avisa en el log cuando el bridge no está configurado', function () {
        config()->set('whatsapp.bridge.url', null);
        Http::fake();
        Log::spy();

        $ok = app(WhatsAppBridgeService::class)->sendText('525551234567', 'Hola');

        expect($ok)->toBeFalse();
        Http::assertNothingSent();
        Log::shouldHaveReceived('warning')->once();
    });

    it('devuelve false cuando el bridge responde con error', function () {
        Http::fake(['bridge.test/*' => Http::response(['error' => 'bad'], 400)]);

        expect(app(WhatsAppBridgeService::class)->sendText('525551234567', 'Hola'))->toBeFalse();
    });

    it('no lanza excepción si el bridge está caído — el pase de asistencia no debe romperse', function () {
        Http::fake(fn () => throw new \Illuminate\Http\Client\ConnectionException('sin red'));

        expect(fn () => app(WhatsAppBridgeService::class)->sendText('525551234567', 'Hola'))
            ->not->toThrow(\Throwable::class);
    });
});

describe('SendTutorNotification listener', function () {
    it('entrega el aviso por el bridge cuando se dispara el evento', function () {
        Http::fake(['bridge.test/*' => Http::response([], 201)]);

        event(new WhatsAppNotification('Tiago ha salido', '525559876543'));

        Http::assertSent(fn ($request) => $request['number'] === '525559876543'
            && $request['text'] === 'Tiago ha salido');
    });
});
