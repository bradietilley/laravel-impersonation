<?php

namespace BradieTilley\Impersonation\Exceptions;

use BradieTilley\Impersonation\ImpersonationManager;

class ForbiddenWhileImpersonatingException extends ImpersonationException
{
    public function __construct(
        public readonly ImpersonationManager $manager,
        string $message,
    ) {
        parent::__construct($message);
    }
}
