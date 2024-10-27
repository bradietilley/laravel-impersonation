<?php

namespace BradieTilley\Impersonation\Http\Middleware;

use BradieTilley\Impersonation\ImpersonationManager;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ForbiddenWhenImpersonating
{
    public const DEFAULT_ERROR = 'You cannot perform this action while impersonating';

    protected static ?Closure $response = null;

    public function __construct(public readonly ImpersonationManager $impersonation)
    {
    }

    public function handle(Request $request, Closure $next): Response
    {
        if ($this->impersonation->isImpersonating()) {
            return static::$response ? (static::$response)($request, $next) : abort(Response::HTTP_FORBIDDEN, static::DEFAULT_ERROR, );
        }

        return $next($request);
    }

    public static function response(?Closure $callback): void
    {
        static::$response = $callback;
    }
}
