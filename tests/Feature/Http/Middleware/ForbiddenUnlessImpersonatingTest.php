<?php

use BradieTilley\Impersonation\ImpersonationManager;

beforeEach(function () {
    ImpersonationManager::configure(
        fn () => true,
    );
});

test('routes can be made forbidden unless impersonating', function (bool $isImpersonating) {
    $user1 = create_a_user();
    $user2 = create_a_user();

    $this->actingAs($user1);

    if ($isImpersonating) {
        ImpersonationManager::make()->impersonate($user2);
    }

    $this->getJson('/forbidden-unless-impersonating')->assertStatus(
        $isImpersonating ? 200 : 403,
    );
})->with([
    true,
    false,
]);
