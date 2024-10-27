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

    'routing' => [
        /**
         * Is routing enabled?
         *
         * If enabled, the start and stop routes will be registered by this package
         * which are /api/impersonation/start and /api/impersonation/stop.
         *
         * If disabled, you manage the controllers yourself and the package will not
         * register any routes.
         */
        'enabled' => true,

        /**
         * The impersonatee model to use, typically this is just the User model.
         *
         * When starting an impersonation, the 'impersonatee' field will contain the
         * primary key of the impersonatee. This model will be the model that the PK
         * is resolved against.
         */
        'impersonatee_model' => \App\Models\User::class,
    ],
];
