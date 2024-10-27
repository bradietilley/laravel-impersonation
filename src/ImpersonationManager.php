<?php

namespace BradieTilley\Impersonation;

use BradieTilley\Impersonation\Contracts\Impersonateable;
use BradieTilley\Impersonation\Events\ImpersonationFinished;
use BradieTilley\Impersonation\Events\ImpersonationStarted;
use BradieTilley\Impersonation\Exceptions\CannotImpersonateUserException;
use BradieTilley\Impersonation\Exceptions\MissingAuthorizationCallbackException;
use BradieTilley\Impersonation\Http\Requests\ImpersonationStartRequest;
use BradieTilley\Impersonation\Http\Requests\ImpersonationStopRequest;
use BradieTilley\Impersonation\Objects\Impersonation;
use Carbon\CarbonImmutable;
use Closure;
use Illuminate\Contracts\Session\Session;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Auth\User;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class ImpersonationManager
{
    public const SESSION_KEY = 'impersonation_data';

    /** @var (Closure(Model&Impersonateable, Model&Impersonateable): bool) Callback to handle authorization logic */
    protected Closure $authorize;

    /** @var (Closure(): (Model&Impersonateable)) Callback to resolve the current authorised user at any given point */
    protected Closure $user;

    /** @var (Closure(Model&Impersonateable): void) Callback to handle to the login logic */
    protected Closure $login;

    /** @var (Closure(string): (Model&Impersonateable)) Callback to handle the resolving of the impersonatee given the ID of the impersonatee */
    protected Closure $resolveImpersonatee;

    /** @var (Closure(ImpersonationStartRequest, ImpersonationManager): Response) Callback to create the HTTP Response after starting impersonation */
    protected Closure $startResponse;

    /** @var (Closure(ImpersonationStopRequest, ImpersonationManager): Response) Callback to create the HTTP Response after stopping impersonation */
    protected Closure $stopResponse;

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
     * Get the authorised user.
     */
    public function user(): Model&Impersonateable
    {
        $this->user ??= fn () => Auth::user();

        $user = ($this->user)();

        return $user;
    }

    /**
     * Login as the given user.
     */
    protected function loginAs(Model&Impersonateable $user): static
    {
        ($this->login)($user);

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

        if ($this->authorize === null) {
            throw MissingAuthorizationCallbackException::make();
        }

        return ($this->authorize)($impersonator, $impersonatee) === true;
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

    public function resolveImpersonatee(string $impersonatee): Model&Impersonateable
    {
        return ($this->resolveImpersonatee)($impersonatee);
    }

    public function getStartResponse(ImpersonationStartRequest $request): Response
    {
        return ($this->startResponse)($request, $this);
    }

    public function getStopResponse(ImpersonationStopRequest $request): Response
    {
        return ($this->stopResponse)($request, $this);
    }

    /**
     * Configure the impersonation manager to work with the current project.
     *
     * @param (Closure(Model&Impersonateable $impersonator, Model&Impersonateable $impersonatee): bool) $authorize Callback to handle authorization logic
     * @param (Closure(): (Model&Impersonateable))|null $user Callback to resolve the current authorised user at any given point
     * @param (Closure(Model&Impersonateable $impersonatee): void)|null $login Callback to handle to the login logic
     * @param (Closure(string $impersonatee): (Model&Impersonateable))|null $resolveImpersonatee Callback to handle the resolving of the impersonatee given the ID of the impersonatee
     * @param (Closure(ImpersonationStartRequest $request): Response)|null $startResponse Callback to create the HTTP Response after starting impersonation
     * @param (Closure(ImpersonationStopRequest $request): Response)|null $stopResponse Callback to create the HTTP Response after stopping impersonation
     */
    public static function configure(
        Closure $authorize,
        Closure|null $user = null,
        Closure|null $login = null,
        Closure|null $resolveImpersonatee = null,
        Closure|null $startResponse = null,
        Closure|null $stopResponse = null,
    ): void {
        $user ??= function () {
            return Auth::user();
        };

        $login ??= function (User $user) {
            return Auth::login($user, Auth::viaRemember());
        };

        $resolveImpersonatee ??= function (string $impersonatee) {
            $model = ImpersonationConfig::getRoutingImpersonateeModel();

            return $model::findOrFail($impersonatee);
        };

        $startResponse ??= function (ImpersonationStartRequest $request) {
            return $request->expectsJson()
                ? response()->json([
                    'success' => true,
                ])
                : redirect()->back();
        };

        $stopResponse ??= function (ImpersonationStopRequest $request) {
            return $request->expectsJson()
                ? response()->json([
                    'success' => true,
                ])
                : redirect()->back();
        };

        static::make()->setConfiguration(
            $authorize,
            $user,
            $login,
            $resolveImpersonatee,
            $startResponse,
            $stopResponse,
        );
    }

    /**
     * @param (Closure(Model&Impersonateable $impersonator, Model&Impersonateable $impersonatee): bool) $authorize Callback to handle authorization logic
     * @param (Closure(): (Model&Impersonateable))|null $user Callback to resolve the current authorised user at any given point
     * @param (Closure(Model&Impersonateable $impersonatee): void)|null $login Callback to handle to the login logic
     * @param (Closure(string $impersonatee): (Model&Impersonateable))|null $resolveImpersonatee Callback to handle the resolving of the impersonatee given the ID of the impersonatee
     * @param (Closure(ImpersonationStartRequest $request, ImpersonationManager $manager): Response)|null $startResponse Callback to create the HTTP Response after starting impersonation
     * @param (Closure(ImpersonationStopRequest $request, ImpersonationManager $manager): Response)|null $stopResponse Callback to create the HTTP Response after stopping impersonation
     */
    public function setConfiguration(
        Closure|null $authorize = null,
        Closure|null $user = null,
        Closure|null $login = null,
        Closure|null $resolveImpersonatee = null,
        Closure|null $startResponse = null,
        Closure|null $stopResponse = null,
    ): void {
        if ($authorize) {
            $this->authorize = $authorize;
        }

        if ($user) {
            $this->user = $user;
        }

        if ($login) {
            $this->login = $login;
        }

        if ($resolveImpersonatee) {
            $this->resolveImpersonatee = $resolveImpersonatee;
        }

        if ($startResponse) {
            $this->startResponse = $startResponse;
        }

        if ($stopResponse) {
            $this->stopResponse = $stopResponse;
        }
    }
}
