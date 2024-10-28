<?php

use BradieTilley\Impersonation\ImpersonationConfig;
use BradieTilley\Impersonation\ImpersonationManager;
use BradieTilley\Impersonation\Objects\Impersonation;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Auth;

test('ImpersonationLevel can be serialized and deserialized', function () {
    $admin = create_a_user();
    $user = create_a_user();

    $level = Impersonation::make($admin, $user, CarbonImmutable::now(), 1);
    $serialized = serialize($level);

    /** @var Impersonation $unserialized */
    $unserialized = unserialize($serialized);

    expect($unserialized->impersonator->is($level->impersonator))->toBeTrue();
    expect($unserialized->impersonatee->is($level->impersonatee))->toBeTrue();
    expect($unserialized->timestamp->isSameSecond($level->timestamp))->toBeTrue();
    expect($unserialized->level === $level->level)->toBeTrue();
});

test('the current Impersonation level can be fetched from the manager', function () {
    $manager = ImpersonationManager::make();
    ImpersonationManager::configure(
        fn () => true,
    );
    ImpersonationConfig::set('max_depth', 4);

    $user1 = create_a_user();
    $user2 = create_a_user();
    $user3 = create_a_user();
    $user4 = create_a_user();

    /**
     * Guest
     */
    $level = $manager->getCurrentImpersonation();
    expect($level)->toBe(null);

    /**
     * Authenticated - No impersonation
     */
    Auth::login($user1);
    $level = $manager->getCurrentImpersonation();
    expect($level)->toBe(null);

    /**
     * Authenticated - impersonated 1
     */
    $manager->impersonate($user2);
    $level = $manager->getCurrentImpersonation();
    expect($level)->toBeInstanceOf(Impersonation::class);
    expect($level->level)->toBe(1);
    expect($level->impersonator->is($user1))->toBeTrue();
    expect($level->impersonatee->is($user2))->toBeTrue();

    /**
     * Authenticated - impersonated 2
     */
    $manager->impersonate($user3);
    $level = $manager->getCurrentImpersonation();
    expect($level)->toBeInstanceOf(Impersonation::class);
    expect($level->level)->toBe(2);
    expect($level->impersonator->is($user2))->toBeTrue();
    expect($level->impersonatee->is($user3))->toBeTrue();

    /**
     * Authenticated - impersonated 3
     */
    $manager->impersonate($user4);
    $level = $manager->getCurrentImpersonation();
    expect($level)->toBeInstanceOf(Impersonation::class);
    expect($level->level)->toBe(3);
    expect($level->impersonator->is($user3))->toBeTrue();
    expect($level->impersonatee->is($user4))->toBeTrue();

    /**
     * Stop Impersonating once
     */
    $manager->stopImpersonating();
    $level = $manager->getCurrentImpersonation();
    expect($level)->toBeInstanceOf(Impersonation::class);
    expect($level->level)->toBe(2);
    expect($level->impersonator->is($user2))->toBeTrue();
    expect($level->impersonatee->is($user3))->toBeTrue();

    /**
     * Stop Impersonating again
     */
    $manager->stopImpersonating();
    $level = $manager->getCurrentImpersonation();
    expect($level)->toBeInstanceOf(Impersonation::class);
    expect($level->level)->toBe(1);
    expect($level->impersonator->is($user1))->toBeTrue();
    expect($level->impersonatee->is($user2))->toBeTrue();

    /**
     * Stop Impersonating again - no impersonation
     */
    $manager->stopImpersonating();
    $level = $manager->getCurrentImpersonation();
    expect($level)->toBe(null);
});
