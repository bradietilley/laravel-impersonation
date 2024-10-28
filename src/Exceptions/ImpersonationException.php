<?php

namespace BradieTilley\Impersonation\Exceptions;

use BradieTilley\Impersonation\Contracts\Impersonateable;
use Exception;

abstract class ImpersonationException extends Exception
{
    public static function cannotImpersonateUser(Impersonateable $impersonator, Impersonateable $impersonatee): CannotImpersonateUserException
    {
        return new CannotImpersonateUserException($impersonator, $impersonatee, 'User cannot impersonate this user');
    }

    public static function unauthenticated(): ImpersonationUnauthenticatedException
    {
        return new ImpersonationUnauthenticatedException('You must be authenticated to impersonate another user');
    }

    public static function missingConfiguration(): MissingImpersonationConfigurationException
    {
        return new MissingImpersonationConfigurationException('Missing Impersonation Configuration: ImpersonationManager::configure(...)');
    }
}
