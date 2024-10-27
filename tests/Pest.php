<?php

use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Workbench\App\Models\User;

uses(Tests\TestCase::class)->in('Feature', 'Unit');

if (! function_exists('test_app_path')) {
    function test_app_path(string $relative = ''): string
    {
        return __DIR__.'/Fixtures/'.ltrim($relative, '/');
    }
}

expect()->extend('toMatchSql', function (string $expect) {
    $clean = function (string|object $sql): string {
        $sql = is_string($sql) ? $sql : $sql->toRawSql();

        $sql = Str::of($sql)
            ->replace(['`', '"', "'"], '')
            ->replaceMatches("/\s+/", ' ')
            ->replaceMatches('/\s*([\(\)])\s*/', '$1')
            ->replace('\\\\', '\\')
            ->lower()
            ->trim()
            ->toString();

        return $sql;
    };

    $actual = $clean($this->value);
    $expect = $clean($expect);

    $this->value = $actual;

    return $this->toBe($expect);
});

if (! function_exists('create_a_user')) {
    function create_a_user(): User
    {
        return User::create([
            'email' => fake()->safeEmail(),
            'name' => fake()->name(),
            'password' => Hash::make(Str::random(10)),
        ]);
    }
}
