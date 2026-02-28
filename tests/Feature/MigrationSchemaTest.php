<?php

use Illuminate\Support\Facades\Schema;

test('kids table has expected columns', function () {
    expect(Schema::hasTable('kids'))->toBeTrue()
        ->and(Schema::hasColumns('kids', [
            'id', 'first_name', 'last_name', 'birth_date', 'gender', 'created_at', 'updated_at',
        ]))->toBeTrue();
});

test('contacts table has expected columns', function () {
    expect(Schema::hasTable('contacts'))->toBeTrue()
        ->and(Schema::hasColumns('contacts', [
            'id', 'first_name', 'last_name', 'phone', 'international_code', 'email', 'created_at', 'updated_at',
        ]))->toBeTrue();
});

test('attendances table has expected columns', function () {
    expect(Schema::hasTable('attendances'))->toBeTrue()
        ->and(Schema::hasColumns('attendances', [
            'id', 'kid_id', 'contact_id', 'check_in', 'check_out', 'status', 'observations', 'deleted_at',
        ]))->toBeTrue();
});

test('qr codes table has expected columns', function () {
    expect(Schema::hasTable('qr_codes'))->toBeTrue()
        ->and(Schema::hasColumns('qr_codes', [
            'id', 'code', 'kid_id', 'qr_image_path', 'status', 'assigned_at', 'created_at', 'updated_at',
        ]))->toBeTrue();
});

test('allergies table has expected columns', function () {
    expect(Schema::hasTable('allergies'))->toBeTrue()
        ->and(Schema::hasColumns('allergies', [
            'id', 'name', 'color', 'created_at', 'updated_at',
        ]))->toBeTrue();
});

test('contact kid pivot table exists', function () {
    expect(Schema::hasTable('contact_kid'))->toBeTrue()
        ->and(Schema::hasColumns('contact_kid', [
            'contact_id', 'kid_id', 'relationship_type',
        ]))->toBeTrue();
});

test('allergy kid pivot table exists', function () {
    expect(Schema::hasTable('allergy_kid'))->toBeTrue()
        ->and(Schema::hasColumns('allergy_kid', [
            'allergy_id', 'kid_id',
        ]))->toBeTrue();
});

test('tutor messages table has expected columns', function () {
    expect(Schema::hasTable('tutor_messages'))->toBeTrue()
        ->and(Schema::hasColumns('tutor_messages', [
            'id', 'label', 'name', 'message', 'description', 'is_active', 'deleted_at',
        ]))->toBeTrue();
});

test('users table has expected columns', function () {
    expect(Schema::hasTable('users'))->toBeTrue()
        ->and(Schema::hasColumns('users', [
            'id', 'name', 'email', 'password', 'created_at', 'updated_at',
        ]))->toBeTrue();
});
