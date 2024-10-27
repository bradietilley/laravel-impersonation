<?php

namespace BradieTilley\Impersonation;

use BradieTilley\Impersonation\Events\ImpersonationFinished;
use BradieTilley\Impersonation\Events\ImpersonationStarted;
use BradieTilley\Impersonation\Exceptions\CannotImpersonateUserException;
use BradieTilley\Impersonation\Exceptions\MissingAuthorisationCallbackException;
use BradieTilley\Impersonation\Objects\Impersonation;
use Carbon\CarbonImmutable;
use Closure;
use Illuminate\Auth\AuthManager;
use Illuminate\Auth\SessionGuard;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Contracts\Session\Session;
use Illuminate\Foundation\Auth\User;
use Illuminate\Http\Request;
use Illuminate\Log\LogManager;
use Psr\Log\LoggerInterface;

class ImpersonationManager
{
    public const SESSION_KEY = 'impersonation_data';

    protected static ?Closure $authorisation = null;

    protected LoggerInterface $logger;

    protected Guard $guard;

    /** @var array<int, Impersonation> */
    protected array $impersonations = [];

    public function __construct(
        protected Session $session,
        protected Request $request,
        protected AuthManager $auth,
        LogManager $log,
    ) {
        $this->logger = $log->channel(ImpersonationConfig::getLogChannel());

        /** @phpstan-ignore-next-line */
        $this->impersonations = $this->session->get(static::SESSION_KEY, []);
    }

    public static function make(): ImpersonationManager
    {
        /** @var ImpersonationManager $instance */
        $instance = app(ImpersonationManager::class);

        return $instance;
    }

    protected function guard(): Guard
    {
        return $this->auth->guard(ImpersonationConfig::getAuthGuard());
    }

    public function user(): User
    {
        /** @phpstan-ignore-next-line */
        return $this->guard()->user() ?? abort(403);
    }

    public function canImpersonate(User $admin, User $user): bool
    {
        if ($admin->is($user)) {
            return false;
        }

        if ($this->level() >= ImpersonationConfig::maxDepth()) {
            return false;
        }

        if (static::$authorisation === null) {
            throw MissingAuthorisationCallbackException::make();
        }

        return (static::$authorisation)($admin, $user);
    }

    public static function authoriseUsing(Closure $callback): void
    {
        static::$authorisation = $callback;
    }

    public function level(): int
    {
        return count($this->impersonations);
    }

    public function impersonate(User $user): static
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

    protected function loginAs(User $user): static
    {
        $guard = $this->guard();

        if ($guard instanceof SessionGuard) {
            $guard->login($user, true);
        } else {
            $guard->setUser($user);
        }

        return $this;
    }

    protected function save(): static
    {
        $this->session->put(static::SESSION_KEY, $this->impersonations);

        return $this;
    }
}
