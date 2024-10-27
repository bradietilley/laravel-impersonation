<?php

namespace BradieTilley\Impersonation\Exceptions;

use Illuminate\Validation\UnauthorizedException;

class CannotImpersonateUserException extends UnauthorizedException
{
    public static function make(): self
    {
        return new self('User cannot impersonate this user');
    }
}
