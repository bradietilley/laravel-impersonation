<?php

use BradieTilley\Impersonation\Events\ImpersonationFinished;
use BradieTilley\Impersonation\Events\ImpersonationStarted;
use BradieTilley\Impersonation\Exceptions\CannotImpersonateUserException;
use BradieTilley\Impersonation\Exceptions\ImpersonationUnauthenticatedException;
use BradieTilley\Impersonation\Exceptions\MissingImpersonationConfigurationException;
use BradieTilley\Impersonation\ImpersonationConfig;
use BradieTilley\Impersonation\ImpersonationManager;
use BradieTilley\Impersonation\Objects\Impersonation;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Event;
use Workbench\App\Models\User;

beforeEach(function () {
    ImpersonationManager::configure(fn () => true);
});

test('ImpersonationManager can impersonate another user until limit reached', function () {
    Event::fake();

    config([
        'impersonation.max_depth' => 3,
    ]);
    ImpersonationConfig::clearCache();

    $admin0 = create_a_user();
    $admin1 = create_a_user();
    $admin2 = create_a_user();
    $admin3 = create_a_user();
    $admin4 = create_a_user();

    $manager = ImpersonationManager::make();

    auth()->login($admin0);
    expect($admin0->is(auth()->user()))->toBeTrue();
    expect($manager->isImpersonating())->toBeFalse();
    expect($manager->level())->toBe(0);

    $manager->impersonate($admin1);
    expect($admin1->is(auth()->user()))->toBeTrue();
    expect($manager->isImpersonating())->toBeTrue();
    expect($manager->level())->toBe(1);

    Event::assertDispatched(function (ImpersonationStarted $event) use ($admin0, $admin1) {
        expect($event->impersonation->impersonator->is($admin0))->toBeTrue();
        expect($event->impersonation->impersonatee->is($admin1))->toBeTrue();
        expect($event->impersonation->level)->toBe(1);

        return true;
    });

    Event::fake();
    $manager->impersonate($admin2);
    expect($admin2->is(auth()->user()))->toBeTrue();
    expect($manager->isImpersonating())->toBeTrue();
    expect($manager->level())->toBe(2);

    Event::assertDispatched(function (ImpersonationStarted $event) use ($admin1, $admin2) {
        expect($event->impersonation->impersonator->is($admin1))->toBeTrue();
        expect($event->impersonation->impersonatee->is($admin2))->toBeTrue();
        expect($event->impersonation->level)->toBe(2);

        return true;
    });

    Event::fake();
    $manager->impersonate($admin3);
    expect($admin3->is(auth()->user()))->toBeTrue();
    expect($manager->isImpersonating())->toBeTrue();
    expect($manager->level())->toBe(3);

    Event::assertDispatched(function (ImpersonationStarted $event) use ($admin2, $admin3) {
        expect($event->impersonation->impersonator->is($admin2))->toBeTrue();
        expect($event->impersonation->impersonatee->is($admin3))->toBeTrue();
        expect($event->impersonation->level)->toBe(3);

        return true;
    });

    Event::fake();
    expect(fn () => $manager->impersonate($admin4))
        ->toThrow(CannotImpersonateUserException::class);
    Event::assertNotDispatched(ImpersonationStarted::class);

    Event::fake();
    $manager->stopImpersonating();
    expect($manager->level())->toBe(2);

    Event::assertDispatched(function (ImpersonationFinished $event) use ($admin2, $admin3) {
        expect($event->impersonation->impersonator->is($admin2))->toBeTrue();
        expect($event->impersonation->impersonatee->is($admin3))->toBeTrue();
        expect($event->impersonation->level)->toBe(3);

        return true;
    });

    Event::fake();
    $manager->impersonate($admin4);
    expect($admin4->is(auth()->user()))->toBeTrue();
    expect($manager->isImpersonating())->toBeTrue();
    expect($manager->level())->toBe(3);

    Event::assertDispatched(function (ImpersonationStarted $event) use ($admin2, $admin4) {
        expect($event->impersonation->impersonator->is($admin2))->toBeTrue();
        expect($event->impersonation->impersonatee->is($admin4))->toBeTrue();
        expect($event->impersonation->level)->toBe(3);

        return true;
    });

    Event::fake();
    $manager->stopImpersonating();
    expect($admin2->is(auth()->user()))->toBeTrue();
    expect($manager->isImpersonating())->toBeTrue();
    expect($manager->level())->toBe(2);

    Event::assertDispatched(function (ImpersonationFinished $event) use ($admin2, $admin4) {
        expect($event->impersonation->impersonator->is($admin2))->toBeTrue();
        expect($event->impersonation->impersonatee->is($admin4))->toBeTrue();
        expect($event->impersonation->level)->toBe(3);

        return true;
    });

    Event::fake();
    $manager->stopImpersonating();
    expect($admin1->is(auth()->user()))->toBeTrue();
    expect($manager->isImpersonating())->toBeTrue();
    expect($manager->level())->toBe(1);

    Event::assertDispatched(function (ImpersonationFinished $event) use ($admin1, $admin2) {
        expect($event->impersonation->impersonator->is($admin1))->toBeTrue();
        expect($event->impersonation->impersonatee->is($admin2))->toBeTrue();
        expect($event->impersonation->level)->toBe(2);

        return true;
    });

    Event::fake();
    $manager->stopImpersonating();
    expect($admin0->is(auth()->user()))->toBeTrue();
    expect($manager->isImpersonating())->toBeFalse();
    expect($manager->level())->toBe(0);

    Event::assertDispatched(function (ImpersonationFinished $event) use ($admin0, $admin1) {
        expect($event->impersonation->impersonator->is($admin0))->toBeTrue();
        expect($event->impersonation->impersonatee->is($admin1))->toBeTrue();
        expect($event->impersonation->level)->toBe(1);

        return true;
    });

    Event::fake();
    $manager->stopImpersonating();
    expect($admin0->is(auth()->user()))->toBeTrue();
    expect($manager->isImpersonating())->toBeFalse();
    expect($manager->level())->toBe(0);
    Event::assertNotDispatched(ImpersonationStarted::class);
});

test('ImpersonationManager will not allow impersonating self', function () {
    $user1 = create_a_user();
    $user2 = User::find($user1->id);

    expect(ImpersonationManager::make()->canImpersonate($user1, $user2))->toBe(false);
});

test('ImpersonationManager will not allow impersonating beyond max depth', function (int $max) {
    $user1 = create_a_user();
    $user2 = create_a_user();

    config([
        'impersonation.max_depth' => $max,
    ]);
    ImpersonationConfig::clearCache();

    $manager = ImpersonationManager::make();

    $generate = function (int $count) use ($user1, $user2): array {
        return Collection::range(1, $count)
            ->map(fn (int $index) => new Impersonation($user1, $user2, CarbonImmutable::now(), $index))
            ->all();
    };

    (new ReflectionProperty($manager, 'impersonations'))->setValue($manager, $generate($max - 1));
    expect($manager->canImpersonate($user1, $user2))->toBe(true);

    (new ReflectionProperty($manager, 'impersonations'))->setValue($manager, $generate($max));
    expect($manager->canImpersonate($user1, $user2))->toBe(false);

    (new ReflectionProperty($manager, 'impersonations'))->setValue($manager, $generate($max + 1));
    expect($manager->canImpersonate($user1, $user2))->toBe(false);
})->with([
    3,
    4,
    5,
]);

test('impersonation manager can stop impersonating through multiple levels', function () {
    $manager = ImpersonationManager::make();
    ImpersonationManager::configure(
        fn () => true,
    );
    ImpersonationConfig::set('max_depth', 3);

    $user1 = create_a_user();
    $user2 = create_a_user();
    $user3 = create_a_user();
    $user4 = create_a_user();

    Auth::login($user1);
    $manager->impersonate($user2)->impersonate($user3)->impersonate($user4);
    expect($manager->level())->toBe(3);

    $manager->stopAllImpersonating();
    expect($manager->isImpersonating())->toBe(false);
});

test('guest users cannot impersonate', function () {
    $manager = ImpersonationManager::make();
    ImpersonationManager::configure(
        fn () => true,
    );

    $user = create_a_user();

    expect(fn () => $manager->impersonate($user))->toThrow(
        ImpersonationUnauthenticatedException::class,
    );
});

test('you must configure the impersonation manager before you can impersonate', function () {
    app()->forgetInstance(ImpersonationManager::class);
    $manager = ImpersonationManager::make();

    $user1 = create_a_user();
    $user2 = create_a_user();

    $this->actingAs($user1);

    expect(fn () => $manager->impersonate($user2))->toThrow(
        MissingImpersonationConfigurationException::class,
    );
});
