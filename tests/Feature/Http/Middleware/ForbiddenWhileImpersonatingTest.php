<?php

use BradieTilley\Impersonation\ImpersonationManager;

beforeEach(function () {
    ImpersonationManager::configure(
        fn () => true,
    );
});

test('routes can be made forbidden when impersonating', function (bool $isImpersonating) {
    $user1 = create_a_user();
    $user2 = create_a_user();

    $this->actingAs($user1);

    if ($isImpersonating) {
        ImpersonationManager::make()->impersonate($user2);
    }

    $this->getJson('/forbidden-when-impersonating')->assertStatus(
        $isImpersonating ? 403 : 200,
    );
})->with([
    true,
    false,
]);
