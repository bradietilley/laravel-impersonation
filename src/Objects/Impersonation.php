<?php

namespace BradieTilley\Impersonation\Objects;

use BradieTilley\Impersonation\Contracts\Impersonateable;
use Carbon\CarbonImmutable;
use Illuminate\Queue\SerializesModels;

class Impersonation
{
    use SerializesModels;

    public function __construct(
        public readonly Impersonateable $impersonator,
        public readonly Impersonateable $impersonatee,
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
