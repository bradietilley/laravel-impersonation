<?php

namespace BradieTilley\Impersonation\Http\Controllers;

use BradieTilley\Impersonation\Http\Requests\ImpersonationStartRequest;
use BradieTilley\Impersonation\ImpersonationManager;
use Closure;
use Illuminate\Contracts\Routing\ResponseFactory;
use Symfony\Component\HttpFoundation\Response;

class ImpersonationStartController
{
    protected static ?Closure $callback = null;

    public function __construct(
        public readonly ImpersonationManager $impersonation,
        public readonly ResponseFactory $response,
    ) {
    }

    /**
     * Impersonate a User
     */
    public function __invoke(ImpersonationStartRequest $request): Response
    {
        $this->impersonation->impersonate($request->impersonatee());

        return static::$callback
            ? (static::$callback)($this)
            : $this->response->json([
                'success' => true,
            ]);
    }

    public static function response(?Closure $callback): void
    {
        static::$callback = $callback;
    }
}
