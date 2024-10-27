<?php

use BradieTilley\Impersonation\ImpersonationManager;

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
        startResponse: fn () => response()->redirectTo('/'),
    );

    $admin = create_a_user();
    $user = create_a_user();

    $this->actingAs($admin)
        ->postJson(route('impersonation.start', [
            'impersonatee' => $user->id,
        ]))
        ->assertRedirect('/');

    expect(ImpersonationManager::make()->isImpersonating())->toBeTrue();
});
