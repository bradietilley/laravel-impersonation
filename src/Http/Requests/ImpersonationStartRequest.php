<?php

namespace BradieTilley\Impersonation\Http\Requests;

use BradieTilley\Impersonation\Contracts\Impersonateable;
use BradieTilley\Impersonation\ImpersonationConfig;
use BradieTilley\Impersonation\ImpersonationManager;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Http\FormRequest;

class ImpersonationStartRequest extends FormRequest
{
    public function authorize(): bool
    {
        return ImpersonationManager::make()->canImpersonate($this->user(), $this->impersonatee());
    }

    public function rules(): array
    {
        return [
            'impersonatee' => [
                'required',
                'alpha',
            ],
        ];
    }

    public function impersonatee(): Model&Impersonateable
    {
        $model = ImpersonationConfig::getRoutingImpersonateeModel();

        /** @var string|int $user */
        $user = $this->input('user');

        if (is_string($user) || is_int($user)) {
        }

        return $user;
    }

    public function user($guard = null): Model&Impersonateable
    {
        /** @var Model&Impersonateable $user */
        $user = parent::user($guard);

        return $user;
    }
}
