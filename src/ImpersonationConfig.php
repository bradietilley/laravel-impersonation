<?php

namespace BradieTilley\Impersonation;

use BradieTilley\Impersonation\Contracts\Impersonateable;
use Illuminate\Database\Eloquent\Model;

class ImpersonationConfig
{
    /** @var array<string, mixed> */
    protected static array $cache = [];

    protected static function get(string $key, mixed $default = null): mixed
    {
        return static::$cache[$key] ??= config("impersonation.{$key}", $default);
    }

    public static function set(string $key, mixed $value): void
    {
        static::$cache[$key] = $value;
    }

    public static function clearCache(): void
    {
        static::$cache = [];
    }

    /**
     * Get the maximum times a user can impersonate.
     *
     * 1 = A single impersonation: Foo impersonating Bar
     * 2 = A double impersonation: Foo impersonating Bar impersonating Baz
     * ...
     */
    public static function maxDepth(): int
    {
        return static::get('max_depth', 1);
    }

    /**
     * Is routing enabled?
     *
     * If enabled, the start and stop routes will be registered by this package
     * which are /api/impersonation/start and /api/impersonation/stop.
     *
     * If disabled, you manage the controllers yourself and the package will not
     * register any routes.
     */
    public static function getRoutingEnabled(): bool
    {
        return static::get('routing.enabled', false);
    }

    /**
     * The impersonatee model to use, typically this is just the User model.
     *
     * When starting an impersonation, the 'impersonatee' field will contain the
     * primary key of the impersonatee. This model will be the model that the PK
     * is resolved against.
     *
     * @return class-string<Impersonateable>
     */
    public static function getRoutingImpersonateeModel(): string
    {
        /** @phpstan-ignore-next-line */
        return static::get('routing.impersonatee_model', \App\Models\User::class);
    }
}
