<?php

use App\Enums\AttendanceStatus;
use App\Enums\QrCodeStatus;
use App\Enums\TutorMessageType;

// QrCodeStatus
test('qr code status has all expected cases', function () {
    $cases = QrCodeStatus::cases();

    expect($cases)->toHaveCount(3)
        ->and(collect($cases)->pluck('name')->all())->toBe(['Available', 'Assigned', 'Lost']);
});

test('qr code status labels are in spanish', function () {
    expect(QrCodeStatus::Available->getLabel())->toBe('Disponible')
        ->and(QrCodeStatus::Assigned->getLabel())->toBe('Asignado')
        ->and(QrCodeStatus::Lost->getLabel())->toBe('Perdido');
});

test('qr code status colors are correct', function () {
    expect(QrCodeStatus::Available->getColor())->toBe('success')
        ->and(QrCodeStatus::Assigned->getColor())->toBe('info')
        ->and(QrCodeStatus::Lost->getColor())->toBe('danger');
});

// AttendanceStatus
test('attendance status has all expected cases', function () {
    $cases = AttendanceStatus::cases();

    expect(count($cases))->toBeGreaterThanOrEqual(2);
});

test('attendance status en clase has correct label', function () {
    expect(AttendanceStatus::EN_CLASE->getLabel())->toBeString();
});

test('attendance status retirado has correct label', function () {
    expect(AttendanceStatus::RETIRADO->getLabel())->toBeString();
});

test('attendance status has colors', function () {
    expect(AttendanceStatus::EN_CLASE->getColor())->toBeString()
        ->and(AttendanceStatus::RETIRADO->getColor())->toBeString();
});

// TutorMessageType
test('tutor message type has expected cases', function () {
    $cases = TutorMessageType::cases();

    expect(count($cases))->toBeGreaterThanOrEqual(5);
});

test('tutor message type has labels', function () {
    foreach (TutorMessageType::cases() as $case) {
        expect($case->getLabel())->toBeString()->not->toBeEmpty();
    }
});
