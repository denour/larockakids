<?php

use Illuminate\Support\Facades\Blade;

/**
 * Every icon the kiosk screens rely on must resolve to a real SVG path.
 * A missing name silently falls back to "info", so we compare the rendered
 * path against the fallback to catch typos and dropped entries.
 */
$names = [
    'whatsapp', 'search', 'user', 'user-plus', 'calendar', 'backpack', 'school', 'shield-health',
    'toilet', 'users-group', 'bell', 'check', 'check-circle', 'pencil', 'lock', 'info', 'phone',
    'chevron-right', 'chevron-left', 'chevron-down', 'globe', 'headset', 'moon', 'nap',
    'graduation-cap', 'heart', 'save', 'star', 'sparkles', 'check-badge',
    'home', 'device', 'chat-bubble', 'whatsapp-outline', 'graduation-cap-solid', 'calendar-check',
];

test('the o-icon component renders a non-empty svg path', function (string $name) {
    $svg = Blade::render('<x-o-icon :name="$name" />', ['name' => $name]);

    expect($svg)->toContain('<svg')->toContain('viewBox=');

    preg_match('/ d="([^"]+)"/', $svg, $matches);

    expect($matches[1] ?? '')->not->toBeEmpty();
})->with($names);

test('every named icon has its own distinct path', function () use ($names) {
    $paths = [];

    foreach ($names as $name) {
        preg_match('/ d="([^"]+)"/', Blade::render('<x-o-icon :name="$name" />', ['name' => $name]), $matches);
        $paths[$name] = $matches[1];
    }

    $fallback = $paths['info'];
    $usingFallback = array_keys(array_filter(
        $paths,
        fn (string $path, string $name) => $name !== 'info' && $path === $fallback,
        ARRAY_FILTER_USE_BOTH
    ));

    expect($usingFallback)->toBe([]);
});

test('brand icons are filled and outline icons are stroked', function () {
    expect(Blade::render('<x-o-icon name="whatsapp" />'))->toContain('fill="currentColor"');
    expect(Blade::render('<x-o-icon name="user" />'))->toContain('stroke="currentColor"');
});

test('overlapping solid icons union instead of punching holes in themselves', function () {
    // The mortarboard is drawn as three overlapping shapes, so the even-odd rule
    // would cancel the overlaps out and leave a hollow cap on the screen.
    expect(Blade::render('<x-o-icon name="graduation-cap-solid" />'))
        ->toContain('fill-rule="nonzero"');

    // The WhatsApp bubble does need even-odd: its glyph is cut out of the bubble.
    expect(Blade::render('<x-o-icon name="whatsapp" />'))
        ->toContain('fill-rule="evenodd"');
});

test('aliases resolve to their canonical icon', function (string $alias, string $canonical) {
    $render = fn (string $name) => Blade::render('<x-o-icon :name="$name" />', ['name' => $name]);

    expect($render($alias))->toBe($render($canonical));
})->with([
    ['nap', 'moon'],
    ['graduation', 'graduation-cap'],
    ['group', 'users-group'],
    ['edit', 'pencil'],
    ['back', 'chevron-left'],
    ['next', 'chevron-right'],
    ['language', 'globe'],
    ['support', 'headset'],
    ['health', 'shield-health'],
    ['screen', 'device'],
    ['mobile', 'device'],
    ['building', 'school'],
    ['classroom', 'home'],
    ['message', 'chat-bubble'],
]);

test('an unknown icon name falls back to the info glyph', function () {
    $render = fn (string $name) => Blade::render('<x-o-icon :name="$name" />', ['name' => $name]);

    expect($render('definitely-not-an-icon'))->toBe($render('info'));
});
