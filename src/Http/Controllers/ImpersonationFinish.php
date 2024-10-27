<?php

namespace BradieTilley\Impersonation\Http\Controllers;

use BradieTilley\Impersonation\Http\Requests\ImpersonationFinishRequest;
use BradieTilley\Impersonation\ImpersonationManager;
use Closure;
use Illuminate\Contracts\Routing\ResponseFactory;
use Symfony\Component\HttpFoundation\Response;

class ImpersonationFinish
{
    protected static ?Closure $callback = null;

    public function __construct(
        public readonly ImpersonationManager $impersonation,
        public readonly ResponseFactory $response,
    ) {
    }

    /**
     * Stop Impersonating
     */
    public function __invoke(ImpersonationFinishRequest $request): Response
    {
        $this->impersonation->stopImpersonating();

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
