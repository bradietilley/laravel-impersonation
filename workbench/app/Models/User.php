<?php

namespace Workbench\App\Models;

use BradieTilley\Impersonation\Contracts\Impersonateable;
use Illuminate\Foundation\Auth\User as AuthUser;

class User extends AuthUser implements Impersonateable
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
        ];
    }
}
