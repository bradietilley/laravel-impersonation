<?php

namespace BradieTilley\Impersonation\Http\Controllers;

use BradieTilley\Impersonation\Http\Requests\ImpersonationStopRequest;
use BradieTilley\Impersonation\ImpersonationManager;
use Illuminate\Contracts\Routing\ResponseFactory;
use Symfony\Component\HttpFoundation\Response;

class ImpersonationStopController
{
    public function __construct(
        public readonly ImpersonationManager $impersonation,
        public readonly ResponseFactory $response,
    ) {
    }

    /**
     * Stop Impersonating
     */
    public function __invoke(ImpersonationStopRequest $request): Response
    {
        $this->impersonation->stopImpersonating();

        return $this->impersonation->getStopResponse($request);
    }
}
