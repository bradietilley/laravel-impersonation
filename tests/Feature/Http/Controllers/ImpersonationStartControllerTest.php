<?php

use BradieTilley\Impersonation\ImpersonationConfig;
use BradieTilley\Impersonation\ImpersonationManager;
use Illuminate\Support\Facades\Auth;

test('the impersonation start endpoint will begin impersonation', function () {
    ImpersonationManager::configure(
        fn () => true,
    );

    $admin = create_a_user();
    $user = create_a_user();

    $this->actingAs($admin)
        ->postJson(route('impersonation.start', [
            'impersonatee' => $user->id,
        ]))
        ->assertOk()
        ->assertJson([
            'success' => true,
        ]);

    expect(ImpersonationManager::make()->isImpersonating())->toBeTrue();
});

test('the impersonation start endpoint will begin impersonation with custom response', function () {
    ImpersonationManager::configure(
        fn () => true,
        startResponse: fn () => response()->redirectTo('/test'),
    );

    $admin = create_a_user();
    $user = create_a_user();

    $this->actingAs($admin)
        ->postJson(route('impersonation.start', [
            'impersonatee' => $user->id,
        ]))
        ->assertRedirect('/test');

    expect(ImpersonationManager::make()->isImpersonating())->toBeTrue();
});

test('the impersonation start endpoint can be accessed through recursive impersonation', function () {
    ImpersonationManager::configure(
        fn () => true,
    );
    ImpersonationConfig::set('max_depth', 3);

    $admin = create_a_user();
    $user = create_a_user();
    $user2 = create_a_user();
    $user3 = create_a_user();

    $this->actingAs($admin);

    expect(ImpersonationManager::make()->level())->toBe(0);
    expect(Auth::id())->toBe($admin->id);

    $this->postJson(route('impersonation.start', [
        'impersonatee' => $user->id,
    ]))->assertOk();

    expect(ImpersonationManager::make()->level())->toBe(1);
    expect(Auth::id())->toBe($user->id);

    $this->postJson(route('impersonation.start', [
        'impersonatee' => $user2->id,
    ]))->assertOk();

    expect(ImpersonationManager::make()->level())->toBe(2);
    expect(Auth::id())->toBe($user2->id);

    $this->postJson(route('impersonation.start', [
        'impersonatee' => $user3->id,
    ]))->assertOk();

    expect(ImpersonationManager::make()->level())->toBe(3);
    expect(Auth::id())->toBe($user3->id);
});
