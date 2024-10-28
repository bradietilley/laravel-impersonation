<?php

namespace BradieTilley\Impersonation\Http\Requests;

use BradieTilley\Impersonation\Contracts\Impersonateable;
use BradieTilley\Impersonation\ImpersonationManager;
use Illuminate\Foundation\Http\FormRequest;

class ImpersonationStartRequest extends FormRequest
{
    public function authorize(): bool
    {
        return ImpersonationManager::make()->canImpersonate($this->user(), $this->impersonatee());
    }

    public function impersonatee(): Impersonateable
    {
        /** @var string $impersonatee */
        $impersonatee = $this->route('impersonatee');

        $impersonatee = ImpersonationManager::make()->resolveImpersonatee($impersonatee);

        return $impersonatee;
    }

    public function user($guard = null): Impersonateable
    {
        /** @var Impersonateable $user */
        $user = parent::user($guard);

        return $user;
    }
}
