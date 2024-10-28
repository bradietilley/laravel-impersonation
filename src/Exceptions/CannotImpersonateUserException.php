<?php

namespace BradieTilley\Impersonation\Exceptions;

use BradieTilley\Impersonation\Contracts\Impersonateable;

class CannotImpersonateUserException extends ImpersonationException
{
    public function __construct(
        public readonly Impersonateable $impersonator,
        public readonly Impersonateable $impersonatee,
        string $message,
    ) {
        parent::__construct($message);
    }
}
