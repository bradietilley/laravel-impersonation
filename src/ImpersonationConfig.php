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

    public static function getLogChannel(): string|null
    {
        return static::get('channel');
    }

    public static function getGateKey(): string
    {
        return static::get('gate_key');
    }

    public static function getAuthGuard(): string|null
    {
        return static::get('auth_guard');
    }

    public static function maxDepth(): int
    {
        return static::get('max_depth', 2);
    }
}
