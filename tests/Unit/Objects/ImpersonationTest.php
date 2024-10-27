<?php

use BradieTilley\Impersonation\Objects\Impersonation;
use Carbon\CarbonImmutable;

test('ImpersonationLevel can be serialized and deserialized', function () {
    $admin = create_a_user();
    $user = create_a_user();

    $level = Impersonation::make($admin, $user, CarbonImmutable::now(), 1);
    $serialized = serialize($level);

    /** @var Impersonation $unserialized */
    $unserialized = unserialize($serialized);

    expect($unserialized->admin->is($level->admin))->toBeTrue();
    expect($unserialized->user->is($level->user))->toBeTrue();
    expect($unserialized->timestamp->isSameSecond($level->timestamp))->toBeTrue();
    expect($unserialized->level === $level->level)->toBeTrue();
});
