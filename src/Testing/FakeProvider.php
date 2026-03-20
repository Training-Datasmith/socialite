<?php

declare (strict_types=1);
namespace Laravel\Socialite\Testing;

use Closure;
use Illuminate\Http\Redirect_Response;
use Illuminate\Support\Traits\Forwards_Calls;
use Laravel\Socialite\Contracts\Provider;
class Fake_Provider implements Provider
{
    use Forwards_Calls;
    /**
     * The driver name.
     *
     * @var string
     */
    protected $driver;
    /**
     * The provider resolver.
     *
     * @var \Closure
     */
    protected $resolver;
    /**
     * The original provider instance.
     *
     * @var \Laravel\Socialite\Contracts\Provider
     */
    protected $provider;
    /**
     * The fake user to return.
     *
     * @var \Laravel\Socialite\Contracts\User|\Closure|array|null
     */
    protected $user;
    /**
     * Create a new fake provider instance.
     *
     * @param  string  $driver
     * @param  \Closure  $resolver
     * @param  \Laravel\Socialite\Contracts\User|\Closure|array|null  $user
     */
    public function __construct($driver, $resolver, $user = null)
    {
        $this->driver = $driver;
        $this->resolver = $resolver;
        $this->user = $user;
    }
    /**
     * Redirect the user to the authentication page for the provider.
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Illuminate\Http\RedirectResponse
     */
    public function redirect()
    {
        return new Redirect_Response('https://socialite.fake/' . $this->driver . '/authorize');
    }
    /**
     * Get the User instance for the authenticated user.
     *
     * @return \Laravel\Socialite\Contracts\User
     */
    public function user()
    {
        if ($this->user instanceof Closure) {
            return ($this->user)();
        }
        return $this->user ?? $this->provider()->user();
    }
    /**
     * Get the original provider instance.
     *
     * @return \Laravel\Socialite\Contracts\Provider
     */
    public function provider()
    {
        return $this->provider ?? $this->provider = ($this->resolver)();
    }
    /**
     * Handle calls to methods that are not available on the fake provider.
     */
    public function __call(string $method, array $parameters)
    {
        return $this->forward_decorated_call_to($this->provider(), $method, $parameters);
    }
}