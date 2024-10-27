<?php

namespace BradieTilley\Impersonation;

class ImpersonationConfig
{
    /** @var array<string, mixed> */
    protected static array $cache = [];

    protected static function get(string $key, mixed $default = null): mixed
    {
        return static::$cache[$key] ??= config("impersonation.{$key}", $default);
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
}
