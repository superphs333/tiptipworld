<?php

use App\Models\Tip;
use Tests\TestCase;

uses(TestCase::class);

function normalizedDateBindings(array $bindings): array
{
    return array_map(
        static fn ($value) => $value instanceof DateTimeInterface
            ? $value->format('Y-m-d H:i:s')
            : $value,
        $bindings
    );
}

test('date only end date expands to the end of day', function () {
    $bindings = normalizedDateBindings(
        Tip::query()->applyDateRange(null, '2026-05-01')->getBindings()
    );

    expect($bindings)->toBe([
        '2026-05-01 23:59:59',
    ]);
});

test('date time end date keeps the explicit cutoff time', function () {
    $bindings = normalizedDateBindings(
        Tip::query()->applyDateRange(null, '2026-05-01 10:30')->getBindings()
    );

    expect($bindings)->toBe([
        '2026-05-01 10:30:00',
    ]);
});

test('reversed date only inputs still expand to full day boundaries after swapping', function () {
    $bindings = normalizedDateBindings(
        Tip::query()->applyDateRange('2026-05-03', '2026-05-01')->getBindings()
    );

    expect($bindings)->toBe([
        '2026-05-01 00:00:00',
        '2026-05-03 23:59:59',
    ]);
});
