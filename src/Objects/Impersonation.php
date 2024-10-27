<?php

namespace BradieTilley\Impersonation\Objects;

use Carbon\CarbonImmutable;
use Illuminate\Foundation\Auth\User;
use Illuminate\Queue\SerializesModels;

class Impersonation
{
    use SerializesModels;

    public function __construct(
        public readonly User $admin,
        public readonly User $user,
        public readonly CarbonImmutable $timestamp,
        public readonly int $level,
    ) {
    }

    public static function make(mixed ...$args): static
    {
        /** @phpstan-ignore-next-line */
        return new static(...$args);
    }
}
