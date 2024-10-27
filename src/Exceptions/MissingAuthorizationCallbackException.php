<?php

namespace BradieTilley\Impersonation\Exceptions;

use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

class MissingAuthorizationCallbackException extends UnauthorizedHttpException
{
    public static function make(): self
    {
        $error = 'Missing Impersonation Configuration: ImpersonationManager::configure(...)';

        return new self($error);
    }
}
