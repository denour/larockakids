<?php

use App\Models\Kid;
use App\Models\OnboardingSession;
use App\Services\OnboardingService;
use Tests\Support\FakeRandomInt;

require_once __DIR__.'/../../Support/random_int_shadow.php';

beforeEach(function () {
    $this->service = app(OnboardingService::class);
    FakeRandomInt::reset();
});

afterEach(function () {
    FakeRandomInt::reset();
});

test('startSession creates a unique pending 6-digit code that expires', function () {
    $this->freezeTime();

    $session = $this->service->startSession();

    expect($session->code)->toBeString()
        ->and($session->code)->toMatch('/^\d{6}$/')
        ->and($session->status)->toBe('pending')
        ->and($session->expires_at)->not->toBeNull()
        ->and($session->expires_at->toDateTimeString())->toBe(now()->addMinutes(15)->toDateTimeString());
});

/**
 * The kiosk promises parents a six-digit code, so the draw has to span exactly
 * 100000..999999: one value narrower and a legitimate code becomes unreachable,
 * one value wider and the screen can print a five- or seven-digit code.
 */
test('startSession draws codes from the whole six-digit space', function () {
    FakeRandomInt::returnBound('min');
    expect($this->service->startSession()->code)->toBe('100000');

    FakeRandomInt::returnBound('max');
    expect($this->service->startSession()->code)->toBe('999999');
});

test('startSession keeps drawing until it finds a code no pending session holds', function () {
    OnboardingSession::factory()->create(['code' => '111111']);

    FakeRandomInt::queue(111111, 111111, 222222);

    expect($this->service->startSession()->code)->toBe('222222');
});

test('startSession reuses a code once the session holding it is no longer pending', function () {
    OnboardingSession::factory()->create(['code' => '111111', 'status' => 'matched']);

    FakeRandomInt::queue(111111);

    expect($this->service->startSession()->code)->toBe('111111');
});

test('findKidByPhone matches regardless of country code and mobile prefix', function (string $incoming) {
    ['kid' => $kid] = createKidWithContact([], ['phone' => '6641234567', 'international_code' => '52']);

    expect($this->service->findKidByPhone($incoming)?->id)->toBe($kid->id);
})->with([
    '5216641234567',      // 52 + mexican mobile 1 + local
    '526641234567',       // 52 + local
    '16641234567',        // mexican mobile 1 + local, no country code
    '+52 664 123 4567',   // formatted
    '6641234567',         // bare local
]);

test('findKidByPhone returns null when nothing matches', function () {
    createKidWithContact([], ['phone' => '6641234567', 'international_code' => '52']);

    expect($this->service->findKidByPhone('5210000000000'))->toBeNull()
        ->and($this->service->findKidByPhone(''))->toBeNull();
});

test('findKidByPhone refuses a blank sender even when a contact has a blank phone', function () {
    createKidWithContact([], ['phone' => '', 'international_code' => '']);

    expect($this->service->findKidByPhone(''))->toBeNull()
        ->and($this->service->findKidByPhone('   '))->toBeNull()
        ->and($this->service->findKidByPhone('+'))->toBeNull();
});

test('searchByName handles zero, one and many matches', function () {
    expect($this->service->searchByName('Nadie Aqui'))->toHaveCount(0);

    Kid::factory()->create(['first_name' => 'Mateo', 'last_name' => 'Hernández']);
    expect($this->service->searchByName('Mateo Hernández'))->toHaveCount(1);

    Kid::factory()->create(['first_name' => 'Mateo', 'last_name' => 'García']);
    expect($this->service->searchByName('Mateo'))->toHaveCount(2);

    expect($this->service->searchByName('   '))->toHaveCount(0);
});

test('searchByName matches a term against either the first or the last name, sorted by first name', function () {
    Kid::factory()->create(['first_name' => 'Renata', 'last_name' => 'Mateos']);
    Kid::factory()->create(['first_name' => 'Mateo', 'last_name' => 'Solís']);
    Kid::factory()->create(['first_name' => 'Bruno', 'last_name' => 'Vega']);

    expect($this->service->searchByName('Mateo')->pluck('first_name')->all())
        ->toBe(['Mateo', 'Renata']);
});

test('searchByName requires every term to match the same kid', function () {
    Kid::factory()->create(['first_name' => 'Mateo', 'last_name' => 'Solís']);
    Kid::factory()->create(['first_name' => 'Renata', 'last_name' => 'Herrera']);

    expect($this->service->searchByName('Mateo Herrera'))->toHaveCount(0)
        ->and($this->service->searchByName('Mateo Solís'))->toHaveCount(1);
});

test('searchByName matches on a partial term', function () {
    Kid::factory()->create(['first_name' => 'Mateo', 'last_name' => 'Solís']);

    expect($this->service->searchByName('ateo'))->toHaveCount(1);
});

/**
 * A stray control byte in the search box must not widen the search. PDO binds
 * strings to SQLite NUL-terminated, so an untrimmed "\0Mateo" reaches the driver
 * as the pattern "%" and quietly matches every kid on the roster. Trimming the
 * raw input is what keeps that from happening.
 */
test('searchByName does not become a match-all when the input carries a stray NUL byte', function () {
    Kid::factory()->create(['first_name' => 'Mateo', 'last_name' => 'Solís']);
    Kid::factory()->create(['first_name' => 'Bruno', 'last_name' => 'Vega']);

    expect($this->service->searchByName("\0Mateo")->pluck('first_name')->all())
        ->toBe(['Mateo']);
});

test('matchInboundMessage extracts a code from free-form text', function () {
    ['kid' => $kid] = createKidWithContact([], ['phone' => '6641234567', 'international_code' => '52']);
    $session = OnboardingSession::factory()->create(['code' => '778899']);

    $matched = $this->service->matchInboundMessage('Hola, mi código es 778899 gracias', '5216641234567');

    expect($matched?->id)->toBe($session->id)
        ->and($session->refresh()->kid_id)->toBe($kid->id);
});

test('matchInboundMessage records the matched kid and the normalized sender phone', function () {
    ['kid' => $kid] = createKidWithContact([], ['phone' => '6641234567', 'international_code' => '52']);
    $session = OnboardingSession::factory()->create(['code' => '778899']);

    $this->service->matchInboundMessage('778899', '+52 1 664 123 4567');

    expect($session->refresh()->status)->toBe('matched')
        ->and($session->kid_id)->toBe($kid->id)
        ->and($session->phone)->toBe('5216641234567');
});

test('matchInboundMessage reads a bounded code even when the message carries other digits', function () {
    createKidWithContact([], ['phone' => '6641234567', 'international_code' => '52']);
    $session = OnboardingSession::factory()->create(['code' => '778899']);

    $matched = $this->service->matchInboundMessage('Codigo 778899 salon 3', '5216641234567');

    expect($matched?->id)->toBe($session->id);
});

test('matchInboundMessage falls back to the digits of a code the parent formatted', function () {
    createKidWithContact([], ['phone' => '6641234567', 'international_code' => '52']);
    $session = OnboardingSession::factory()->create(['code' => '778899']);

    $matched = $this->service->matchInboundMessage('77-88-99', '5216641234567');

    expect($matched?->id)->toBe($session->id);
});

/**
 * Both rows below deliberately hold a code of the wrong length, so the only thing
 * standing between the message and a false match is the six-digit check itself.
 *
 * Note the 7-digit row leans on the test connection not enforcing the
 * `string('code', 6)` width (SQLite does not). On a strict MySQL/Postgres test
 * connection that row would be rejected and this half of the test would need to
 * go — at which point the "too long" branch becomes unobservable, since no row
 * could ever hold a 7-character code.
 */
test('matchInboundMessage rejects anything that is not exactly six digits', function () {
    createKidWithContact([], ['phone' => '6641234567', 'international_code' => '52']);
    OnboardingSession::factory()->create(['code' => '77889']);
    OnboardingSession::factory()->create(['code' => '7788991']);

    expect($this->service->matchInboundMessage('77889', '5216641234567'))->toBeNull()
        ->and($this->service->matchInboundMessage('7788991', '5216641234567'))->toBeNull();
});

test('matchInboundMessage returns null without a valid code', function () {
    createKidWithContact([], ['phone' => '6641234567', 'international_code' => '52']);
    OnboardingSession::factory()->create(['code' => '778899']);

    expect($this->service->matchInboundMessage('sin codigo aqui', '5216641234567'))->toBeNull();
});

test('matchInboundMessage returns null when no session holds that code', function () {
    createKidWithContact([], ['phone' => '6641234567', 'international_code' => '52']);

    expect($this->service->matchInboundMessage('123456', '5216641234567'))->toBeNull();
});

test('matchInboundMessage leaves the session pending when the sender is unknown', function () {
    createKidWithContact([], ['phone' => '6641234567', 'international_code' => '52']);
    $session = OnboardingSession::factory()->create(['code' => '778899']);

    expect($this->service->matchInboundMessage('778899', '5219990000000'))->toBeNull()
        ->and($session->refresh()->status)->toBe('pending')
        ->and($session->kid_id)->toBeNull()
        ->and($session->phone)->toBeNull();
});

test('matchInboundMessage picks the session that holds the code, not the newest one', function () {
    ['kid' => $kid] = createKidWithContact([], ['phone' => '6641234567', 'international_code' => '52']);
    $wanted = OnboardingSession::factory()->create(['code' => '111111']);
    OnboardingSession::factory()->create(['code' => '222222']);

    expect($this->service->matchInboundMessage('111111', '5216641234567')?->id)->toBe($wanted->id);
});

test('matchInboundMessage ignores sessions that are no longer pending', function () {
    createKidWithContact([], ['phone' => '6641234567', 'international_code' => '52']);
    $session = OnboardingSession::factory()->create(['code' => '778899', 'status' => 'matched']);

    expect($this->service->matchInboundMessage('778899', '5216641234567'))->toBeNull()
        ->and($session->refresh()->kid_id)->toBeNull();
});

test('matchInboundMessage ignores an expired session', function () {
    createKidWithContact([], ['phone' => '6641234567', 'international_code' => '52']);
    $session = OnboardingSession::factory()->expired()->create(['code' => '778899']);

    expect($this->service->matchInboundMessage('778899', '5216641234567'))->toBeNull()
        ->and($session->refresh()->status)->toBe('pending')
        ->and($session->kid_id)->toBeNull();
});
