<?php

namespace BradieTilley\Impersonation\Http\Requests;

use BradieTilley\Impersonation\ImpersonationManager;
use Illuminate\Foundation\Auth\User;
use Illuminate\Foundation\Http\FormRequest;

class ImpersonationStartRequest extends FormRequest
{
    public function authorize(): bool
    {
        return ImpersonationManager::make()->canImpersonate($this->user(), $this->impersonatee());
    }

    public function impersonatee(): User
    {
        /** @var User $user */
        $user = $this->route('user');

        return $user;
    }

    public function user($guard = null): User
    {
        /** @var User $user */
        $user = parent::user($guard);

        return $user;
    }
}
