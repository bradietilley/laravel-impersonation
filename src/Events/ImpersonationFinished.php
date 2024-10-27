<?php

namespace BradieTilley\Impersonation\Events;

use BradieTilley\Impersonation\Objects\Impersonation;

class ImpersonationFinished extends ImpersonationEvent
{
    public function __construct(public readonly Impersonation $impersonation)
    {
    }
}
