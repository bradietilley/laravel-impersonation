<?php

namespace BradieTilley\Impersonation\Http\Controllers;

use BradieTilley\Impersonation\Http\Requests\ImpersonationStartRequest;
use BradieTilley\Impersonation\ImpersonationManager;
use Illuminate\Contracts\Routing\ResponseFactory;
use Symfony\Component\HttpFoundation\Response;

class ImpersonationStartController
{
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

        return $this->impersonation->getStartResponse($request);
    }
}
