<?php

namespace BradieTilley\Impersonation\Contracts;

use Illuminate\Database\Eloquent\Model;

/**
 * @mixin Model
 *
 * @method bool is(Model|Impersonateable $model)
 * @method static static findOrFail(string|int $identifier)
 * @method static ?static find(string|int $identifier)
 */
interface Impersonateable
{
}
