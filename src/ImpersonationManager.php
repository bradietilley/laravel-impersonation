<?php

namespace BradieTilley\Impersonation;

use BradieTilley\Impersonation\Contracts\Impersonateable;
use BradieTilley\Impersonation\Events\ImpersonationFinished;
use BradieTilley\Impersonation\Events\ImpersonationStarted;
use BradieTilley\Impersonation\Exceptions\CannotImpersonateUserException;
use BradieTilley\Impersonation\Exceptions\MissingAuthorizationCallbackException;
use BradieTilley\Impersonation\Objects\Impersonation;
use Carbon\CarbonImmutable;
use Closure;
use Illuminate\Contracts\Session\Session;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Auth\User;
use Illuminate\Support\Facades\Auth;

class ImpersonationManager
{
    public const SESSION_KEY = 'impersonation_data';

    protected static ?Closure $authorize = null;

    protected static ?Closure $user = null;

    protected static ?Closure $login = null;

    /** @var array<int, Impersonation> */
    protected array $impersonations = [];

    public function __construct(protected Session $session)
    {
        /** @phpstan-ignore-next-line */
        $this->impersonations = $this->session->get(static::SESSION_KEY, []);
    }

    public static function make(): ImpersonationManager
    {
        /** @var ImpersonationManager $instance */
        $instance = app(ImpersonationManager::class);

        return $instance;
    }

    /**
     * Set the handler for authorizing who can impersonate who
     *
     * @param (Closure(Model&Impersonateable $impersonator, Model&Impersonateable $impersonatee): bool)
     */
    public static function authorizeUsing(Closure $callback): void
    {
        static::$authorize = $callback;
    }

    /**
     * Check if the given impersonator can impersonate the impersonatee
     *
     * @throws \BradieTilley\Impersonation\Exceptions\MissingAuthorizationCallbackException
     */
    protected function checkAuthorization(Model&Impersonateable $impersonator, Model&Impersonateable $impersonatee): bool
    {
        if (static::$authorize === null) {
            throw MissingAuthorizationCallbackException::make();
        }

        return (static::$authorize)($impersonator, $impersonatee) === true;
    }

    /**
     * Set the handler for resolving the authorised user.
     *
     * @param (Closure(): Model&Impersonateable) $callback
     */
    public static function resolveUser(Closure $callback): void
    {
        static::$user = $callback;
    }

    /**
     * Get the authorised user.
     */
    public function user(): Model&Impersonateable
    {
        static::$user ??= fn () => Auth::user();

        $user = (static::$user)();

        return $user;
    }

    /**
     * Set the handler for logging in as a user.
     *
     * @param (Closure(Model&Impersonateable $user)) $callback
     */
    public static function loginUsing(Closure $callback): void
    {
        static::$login = $callback;
    }

    /**
     * Login as the given user.
     */
    protected function loginAs(Model&Impersonateable $user): static
    {
        static::$login ??= fn (User $user) => Auth::login($user);

        (static::$login)($user);

        return $this;
    }

    /**
     * Get the current level of impersonate (how deeply impersonated
     * are we?).
     */
    public function level(): int
    {
        return count($this->impersonations);
    }

    /**
     * Check if the given impersonator can impersonate the impersonatee
     */
    public function canImpersonate(Model&Impersonateable $impersonator, Model&Impersonateable $impersonatee): bool
    {
        if ($impersonator->is($impersonatee)) {
            return false;
        }

        if ($this->level() >= ImpersonationConfig::maxDepth()) {
            return false;
        }

        return $this->checkAuthorization($impersonator, $impersonatee);
    }

    public function impersonate(Model&Impersonateable $user): static
    {
        $admin = $this->user();

        if ($this->canImpersonate($admin, $user) === false) {
            throw CannotImpersonateUserException::make();
        }

        $now = CarbonImmutable::now();
        $level = $this->level() + 1;
        $level = Impersonation::make($admin, $user, $now, $level);

        $this->impersonations[] = $level;
        $this->loginAs($user);
        $this->save();

        ImpersonationStarted::dispatch($level);

        return $this;
    }

    public function isImpersonating(): bool
    {
        return ! empty($this->impersonations);
    }

    public function stopImpersonating(): static
    {
        if (empty($this->impersonations)) {
            return $this;
        }

        $impersonation = array_pop($this->impersonations);

        ImpersonationFinished::dispatch($impersonation);

        $this->loginAs($impersonation->admin);
        $this->save();

        return $this;
    }

    protected function save(): static
    {
        $this->session->put(static::SESSION_KEY, $this->impersonations);

        return $this;
    }
}
