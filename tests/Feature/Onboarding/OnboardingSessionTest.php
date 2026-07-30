<?php

use App\Models\Kid;
use App\Models\OnboardingSession;

test('a fresh session is pending and neither matched nor expired', function () {
    $session = OnboardingSession::factory()->create();

    expect($session->isPending())->toBeTrue()
        ->and($session->isMatched())->toBeFalse()
        ->and($session->isExpired())->toBeFalse();
});

test('an expired session is not pending', function () {
    $session = OnboardingSession::factory()->expired()->create();

    expect($session->isExpired())->toBeTrue()
        ->and($session->isPending())->toBeFalse();
});

test('a matched session reports matched and exposes its kid', function () {
    $kid = Kid::factory()->create();
    $session = OnboardingSession::factory()->matched($kid->id, '526641234567')->create();

    expect($session->isMatched())->toBeTrue()
        ->and($session->isPending())->toBeFalse()
        ->and($session->kid->id)->toBe($kid->id);
});
