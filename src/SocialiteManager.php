<?php

declare (strict_types=1);
namespace Laravel\Socialite;

use Illuminate\Support\Arr;
use Illuminate\Support\Manager;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Laravel\Socialite\Exceptions\Driver_Missing_Configuration_Exception;
use Laravel\Socialite\One\Twitter_Provider;
use Laravel\Socialite\Two\Bitbucket_Provider;
use Laravel\Socialite\Two\Facebook_Provider;
use Laravel\Socialite\Two\Github_Provider;
use Laravel\Socialite\Two\Gitlab_Provider;
use Laravel\Socialite\Two\Google_Provider;
use Laravel\Socialite\Two\Linked_In_Open_Id_Provider;
use Laravel\Socialite\Two\Linked_In_Provider;
use Laravel\Socialite\Two\Slack_Open_Id_Provider;
use Laravel\Socialite\Two\Slack_Provider;
use Laravel\Socialite\Two\Twitch_Provider;
use Laravel\Socialite\Two\Twitter_Provider as TwitterOAuth2Provider;
use Laravel\Socialite\Two\X_Provider;
use League\O_Auth1\Client\Server\Twitter as TwitterServer;
class Socialite_Manager extends Manager implements Contracts\Factory
{
    /**
     * The application instance.
     *
     * @var \Illuminate\Contracts\Foundation\Application
     *
     * @deprecated Will be removed in a future Socialite release.
     */
    protected $app;
    /**
     * Get a driver instance.
     *
     * @param  string  $driver
     * @return mixed
     */
    public function with($driver)
    {
        return $this->driver($driver);
    }
    /**
     * Create an instance of the specified driver.
     *
     * @return \Laravel\Socialite\Two\AbstractProvider
     */
    protected function create_github_driver()
    {
        $config = $this->config->get('services.github');
        return $this->build_provider(Github_Provider::class, $config);
    }
    /**
     * Create an instance of the specified driver.
     *
     * @return \Laravel\Socialite\Two\AbstractProvider
     */
    protected function create_facebook_driver()
    {
        $config = $this->config->get('services.facebook');
        return $this->build_provider(Facebook_Provider::class, $config);
    }
    /**
     * Create an instance of the specified driver.
     *
     * @return \Laravel\Socialite\Two\AbstractProvider
     */
    protected function create_google_driver()
    {
        $config = $this->config->get('services.google');
        return $this->build_provider(Google_Provider::class, $config);
    }
    /**
     * Create an instance of the specified driver.
     *
     * @return \Laravel\Socialite\Two\AbstractProvider
     */
    protected function create_linkedin_driver()
    {
        $config = $this->config->get('services.linkedin');
        return $this->build_provider(Linked_In_Provider::class, $config);
    }
    /**
     * Create an instance of the specified driver.
     *
     * @return \Laravel\Socialite\Two\AbstractProvider
     */
    protected function create_linkedin_openid_driver()
    {
        $config = $this->config->get('services.linkedin-openid');
        return $this->build_provider(Linked_In_Open_Id_Provider::class, $config);
    }
    /**
     * Create an instance of the specified driver.
     *
     * @return \Laravel\Socialite\Two\AbstractProvider
     */
    protected function create_bitbucket_driver()
    {
        $config = $this->config->get('services.bitbucket');
        return $this->build_provider(Bitbucket_Provider::class, $config);
    }
    /**
     * Create an instance of the specified driver.
     *
     * @return \Laravel\Socialite\Two\AbstractProvider
     */
    protected function create_gitlab_driver()
    {
        $config = $this->config->get('services.gitlab');
        return $this->build_provider(Gitlab_Provider::class, $config)->set_host($config['host'] ?? null);
    }
    /**
     * Create an instance of the specified driver.
     *
     * @return \Laravel\Socialite\One\AbstractProvider|\Laravel\Socialite\Two\AbstractProvider
     */
    protected function create_twitter_driver()
    {
        $config = $this->config->get('services.twitter');
        if (($config['oauth'] ?? null) === 2) {
            return $this->create_twitter_o_auth2driver();
        }
        return new Twitter_Provider($this->container->make('request'), new Twitter_Server($this->format_config($config)));
    }
    /**
     * Create an instance of the specified driver.
     *
     * @return \Laravel\Socialite\Two\AbstractProvider
     */
    protected function create_twitter_o_auth2driver()
    {
        $config = $this->config->get('services.twitter') ?? $this->config->get('services.twitter-oauth-2');
        return $this->build_provider(Twitter_O_Auth2provider::class, $config);
    }
    /**
     * Create an instance of the specified driver.
     *
     * @return \Laravel\Socialite\Two\AbstractProvider
     */
    protected function create_x_driver()
    {
        $config = $this->config->get('services.x') ?? $this->config->get('services.x-oauth-2');
        return $this->build_provider(X_Provider::class, $config);
    }
    /**
     * Create an instance of the specified driver.
     *
     * @return \Laravel\Socialite\Two\AbstractProvider
     */
    protected function create_twitch_driver()
    {
        $config = $this->config->get('services.twitch');
        return $this->build_provider(Twitch_Provider::class, $config);
    }
    /**
     * Create an instance of the specified driver.
     *
     * @return \Laravel\Socialite\Two\AbstractProvider
     */
    protected function create_slack_driver()
    {
        $config = $this->config->get('services.slack');
        return $this->build_provider(Slack_Provider::class, $config);
    }
    /**
     * Create an instance of the specified driver.
     *
     * @return \Laravel\Socialite\Two\AbstractProvider
     */
    protected function create_slack_openid_driver()
    {
        $config = $this->config->get('services.slack-openid');
        return $this->build_provider(Slack_Open_Id_Provider::class, $config);
    }
    /**
     * Build an OAuth 2 provider instance.
     *
     * @param  string  $provider
     * @param  array  $config
     * @return \Laravel\Socialite\Two\AbstractProvider
     */
    public function build_provider($provider, $config)
    {
        $required_keys = ['client_id', 'client_secret', 'redirect'];
        $missing_keys = array_diff($required_keys, array_keys($config ?? []));
        if (!empty($missing_keys)) {
            throw Driver_Missing_Configuration_Exception::make($provider, $missing_keys);
        }
        return (new $provider($this->container->make('request'), $config['client_id'], $config['client_secret'], $this->format_redirect_url($config), Arr::get($config, 'guzzle', [])))->scopes($config['scopes'] ?? []);
    }
    /**
     * Format the server configuration.
     *
     * @return array
     */
    public function format_config(array $config)
    {
        return array_merge(['identifier' => $config['client_id'], 'secret' => $config['client_secret'], 'callback_uri' => $this->format_redirect_url($config)], $config);
    }
    /**
     * Format the callback URL, resolving a relative URI if needed.
     *
     * @return string
     */
    protected function format_redirect_url(array $config)
    {
        $redirect = value($config['redirect']);
        return Str::starts_with($redirect ?? '', '/') ? $this->container->make('url')->to($redirect) : $redirect;
    }
    /**
     * Forget all of the resolved driver instances.
     *
     * @return $this
     */
    public function forget_drivers()
    {
        $this->drivers = [];
        return $this;
    }
    /**
     * Set the container instance used by the manager.
     *
     * @param  \Illuminate\Contracts\Container\Container  $container
     * @return $this
     */
    public function set_container($container)
    {
        $this->app = $container;
        $this->container = $container;
        $this->config = $container->make('config');
        return $this;
    }
    /**
     * Get the default driver name.
     *
     * @return string
     *
     * @throws \InvalidArgumentException
     */
    public function get_default_driver()
    {
        throw new InvalidArgumentException('No Socialite driver was specified.');
    }
}