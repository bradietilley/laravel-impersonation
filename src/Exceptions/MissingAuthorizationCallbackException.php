<?php

namespace BradieTilley\Impersonation\Exceptions;

use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

class MissingAuthorizationCallbackException extends UnauthorizedHttpException
{
    public static function make(): self
    {
        $error = 'Missing impersonation callback: ImpersonationManager::authorizeUsing(fn () => ...)';

        return new self($error);
    }
}
