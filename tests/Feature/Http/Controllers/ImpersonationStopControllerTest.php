<?php

use BradieTilley\Impersonation\ImpersonationConfig;
use BradieTilley\Impersonation\ImpersonationManager;
use Illuminate\Support\Facades\Auth;

test('the impersonation stop endpoint will begin impersonation', function () {
    ImpersonationManager::configure(
        fn () => true,
    );

    $admin = create_a_user();
    $user = create_a_user();

    $this->actingAs($admin);

    ImpersonationManager::make()->impersonate($user);
    expect(ImpersonationManager::make()->isImpersonating())->toBeTrue();

    $this->postJson(route('impersonation.stop'))
        ->assertOk()
        ->assertJson([
            'success' => true,
        ]);

    expect(ImpersonationManager::make()->isImpersonating())->toBeFalse();
});

test('the impersonation stop endpoint will begin impersonation with custom response', function () {
    ImpersonationManager::configure(
        fn () => true,
        stopResponse: fn () => response()->redirectTo('/test'),
    );

    $admin = create_a_user();
    $user = create_a_user();

    $this->actingAs($admin);

    ImpersonationManager::make()->impersonate($user);
    expect(ImpersonationManager::make()->isImpersonating())->toBeTrue();

    $this->postJson(route('impersonation.stop'))
        ->assertRedirect('/test');

    expect(ImpersonationManager::make()->isImpersonating())->toBeFalse();
});

test('the impersonation stop endpoint can be accessed through recursive impersonation', function () {
    ImpersonationManager::configure(
        fn () => true,
    );
    ImpersonationConfig::set('max_depth', 3);

    $admin = create_a_user();
    $user = create_a_user();
    $user2 = create_a_user();
    $user3 = create_a_user();

    $this->actingAs($admin);

    $manager = ImpersonationManager::make();

    $manager->impersonate($user);
    $manager->impersonate($user2);
    $manager->impersonate($user3);

    expect(ImpersonationManager::make()->level())->toBe(3);
    expect(Auth::id())->toBe($user3->id);

    $this->actingAs($admin)
        ->postJson(route('impersonation.stop'))
        ->assertOk();

    expect(ImpersonationManager::make()->level())->toBe(2);
    expect(Auth::id())->toBe($user2->id);

    $this->actingAs($admin)
    ->postJson(route('impersonation.stop'))
        ->assertOk();

    expect(ImpersonationManager::make()->level())->toBe(1);
    expect(Auth::id())->toBe($user->id);

    $this->actingAs($admin)
    ->postJson(route('impersonation.stop'))
        ->assertOk();

    expect(ImpersonationManager::make()->level())->toBe(0);
    expect(Auth::id())->toBe($admin->id);
});
