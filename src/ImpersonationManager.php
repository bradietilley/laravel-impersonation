<?php

namespace BradieTilley\Impersonation;

use BradieTilley\Impersonation\Contracts\Impersonateable;
use BradieTilley\Impersonation\Events\ImpersonationFinished;
use BradieTilley\Impersonation\Events\ImpersonationStarted;
use BradieTilley\Impersonation\Events\ImpersonationStarting;
use BradieTilley\Impersonation\Exceptions\ImpersonationException;
use BradieTilley\Impersonation\Http\Requests\ImpersonationStartRequest;
use BradieTilley\Impersonation\Http\Requests\ImpersonationStopRequest;
use BradieTilley\Impersonation\Objects\Impersonation;
use Carbon\CarbonImmutable;
use Closure;
use Illuminate\Contracts\Session\Session;
use Illuminate\Foundation\Auth\User;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class ImpersonationManager
{
    public const SESSION_KEY = 'impersonation_data';

    /** @var null|(Closure(Impersonateable, Impersonateable): bool) Callback to handle authorization logic */
    protected ?Closure $authorize = null;

    /** @var (Closure(): (Impersonateable|null)) Callback to resolve the current authorised user at any given point */
    protected Closure $user;

    /** @var (Closure(Impersonateable): void) Callback to handle to the login logic */
    protected Closure $login;

    /** @var (Closure(string): (Impersonateable)) Callback to handle the resolving of the impersonatee given the ID of the impersonatee */
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
    public function user(): Impersonateable
    {
        $user = ($this->user)();

        if ($user === null) {
            throw ImpersonationException::unauthenticated();
        }

        return $user;
    }

    /**
     * Login as the given user.
     */
    protected function loginAs(Impersonateable $user): static
    {
        ($this->login)($user);

        return $this;
    }

    /**
     * Get the current level of impersonation (how deeply impersonated
     * are we?).
     */
    public function level(): int
    {
        return count($this->impersonations);
    }

    /**
     * Get the current level of impersonation as an Impersonation DTO
     */
    public function getCurrentImpersonation(): ?Impersonation
    {
        return $this->impersonations ? $this->impersonations[array_key_last($this->impersonations)] : null;
    }

    /**
     * Check if the given impersonator can impersonate the impersonatee
     */
    public function canImpersonate(Impersonateable $impersonator, Impersonateable $impersonatee): bool
    {
        if ($impersonator->is($impersonatee)) {
            return false;
        }

        if ($this->level() >= ImpersonationConfig::maxDepth()) {
            return false;
        }

        if ($this->authorize === null) {
            throw ImpersonationException::missingConfiguration();
        }

        return ($this->authorize)($impersonator, $impersonatee) === true;
    }

    public function impersonate(Impersonateable $user): static
    {
        $admin = $this->user();

        if ($this->canImpersonate($admin, $user) === false) {
            throw ImpersonationException::cannotImpersonateUser($admin, $user);
        }

        $now = CarbonImmutable::now();
        $level = $this->level() + 1;
        $level = Impersonation::make($admin, $user, $now, $level);

        ImpersonationStarting::dispatch($level);

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

        $this->loginAs($impersonation->impersonator);
        $this->save();

        return $this;
    }

    protected function save(): static
    {
        $this->session->put(static::SESSION_KEY, $this->impersonations);

        return $this;
    }

    public function resolveImpersonatee(string $impersonatee): Impersonateable
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
     * Any configuration callbacks omitted will be handled by default handlers.
     *
     * @param (Closure(Impersonateable $impersonator, Impersonateable $impersonatee): bool) $authorize Callback to handle authorization logic
     * @param (Closure(): (Impersonateable|null))|null $user Callback to resolve the current authorised user at any given point
     * @param (Closure(Impersonateable $impersonatee): void)|null $login Callback to handle to the login logic
     * @param (Closure(string $impersonatee): (Impersonateable))|null $resolveImpersonatee Callback to handle the resolving of the impersonatee given the ID of the impersonatee
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
        static::make()->setConfiguration(
            $authorize,
            $user ??= static::defaultUserHandler(...),
            $login ??= static::defaultLoginHandler(...),
            $resolveImpersonatee ??= static::defaultResolveImpersonateeHandler(...),
            $startResponse ??= static::defaultStartResponseHandler(...),
            $stopResponse ??= static::defaultStopResponseHandler(...),
        );
    }

    /**
     * @param (Closure(Impersonateable $impersonator, Impersonateable $impersonatee): bool) $authorize Callback to handle authorization logic
     * @param (Closure(): (Impersonateable|null))|null $user Callback to resolve the current authorised user at any given point
     * @param (Closure(Impersonateable $impersonatee): void)|null $login Callback to handle to the login logic
     * @param (Closure(string $impersonatee): (Impersonateable))|null $resolveImpersonatee Callback to handle the resolving of the impersonatee given the ID of the impersonatee
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

    /**
     * The default handler for identifying the current logged in user.
     *
     * By default this is a simple `Auth::user()` call
     */
    protected static function defaultUserHandler(): ?Impersonateable
    {
        $user = Auth::user();

        if (! $user instanceof Impersonateable) {
            return null;
        }

        return $user;
    }

    /**
     * The default handler for logging in as an impersonateable.
     *
     * By default this is a simple `Auth::login()` call
     */
    protected static function defaultLoginHandler(Impersonateable $user): void
    {
        /** @var User $user */
        Auth::login($user, Auth::viaRemember());
    }

    /**
     * The default handler for resolving the impersonatee by the given identifier string (ID, etc).
     *
     * By default this is a simple `<ImpersonateeModel>::findOrFail()` call
     */
    protected static function defaultResolveImpersonateeHandler(string $impersonatee): Impersonateable
    {
        $model = ImpersonationConfig::getRoutingImpersonateeModel();

        return $model::findOrFail($impersonatee);
    }

    /**
     * The default handler for producing the Response after starting impersonation via the API.
     *
     * By default this is a simple JSON response or direct back.
     */
    protected static function defaultStartResponseHandler(ImpersonationStartRequest $request): Response
    {
        return $request->expectsJson()
            ? response()->json([
                'success' => true,
            ])
            : redirect()->back();
    }

    /**
     * The default handler for producing the Response after stopping impersonation via the API.
     *
     * By default this is a simple JSON response or direct back.
     */
    protected static function defaultStopResponseHandler(ImpersonationStopRequest $request): Response
    {
        return $request->expectsJson()
            ? response()->json([
                'success' => true,
            ])
            : redirect()->back();
    }
}
