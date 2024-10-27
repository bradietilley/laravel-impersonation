<?php

namespace BradieTilley\Impersonation\Http\Requests;

use BradieTilley\Impersonation\ImpersonationManager;
use Illuminate\Foundation\Http\FormRequest;

class ImpersonationFinishRequest extends FormRequest
{
    public function authorize(): bool
    {
        return ImpersonationManager::make()->isImpersonating();
    }
}
