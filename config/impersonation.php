<?php

return [
    /**
     * The maximum times a user can impersonate.
     *
     * 1 = A single impersonation: Foo impersonating Bar
     * 2 = A double impersonation: Foo impersonating Bar impersonating Baz
     * ...
     *
     * @var int
     */
    'max_depth' => 1,
];
