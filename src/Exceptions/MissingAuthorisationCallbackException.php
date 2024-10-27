<?php

namespace BradieTilley\Impersonation\Exceptions;

use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

class MissingAuthorisationCallbackException extends UnauthorizedHttpException
{
    public static function make(): self
    {
        return new self('Cannot Impersonate due to missing authorisation callback');
    }
}
