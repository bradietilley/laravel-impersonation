<?php

namespace BradieTilley\Impersonation\Http\Middleware;

use BradieTilley\Impersonation\Exceptions\ImpersonationException;
use BradieTilley\Impersonation\ImpersonationManager;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ForbiddenUnlessImpersonating
{
    public function __construct(public readonly ImpersonationManager $impersonation)
    {
    }

    public function handle(Request $request, Closure $next): Response
    {
        if ($this->impersonation->isImpersonating() === false) {
            throw ImpersonationException::forbiddenUnlessImpersonating($this->impersonation);
        }

        return $next($request);
    }
}
