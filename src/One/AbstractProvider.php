<?php

declare (strict_types=1);
namespace Laravel\Socialite\One;

use Illuminate\Http\Redirect_Response;
use Illuminate\Http\Request;
use Laravel\Socialite\Contracts\Provider as ProviderContract;
use League\O_Auth1\Client\Credentials\Token_Credentials;
use League\O_Auth1\Client\Server\Server;
abstract class Abstract_Provider implements Provider_Contract
{
    /**
     * The HTTP request instance.
     *
     * @var \Illuminate\Http\Request
     */
    protected $request;
    /**
     * The OAuth server implementation.
     *
     * @var \League\OAuth1\Client\Server\Server
     */
    protected $server;
    /**
     * A hash representing the last requested user.
     *
     * @var string
     */
    protected $user_hash;
    /**
     * Create a new provider instance.
     */
    public function __construct(Request $request, Server $server)
    {
        $this->server = $server;
        $this->request = $request;
    }
    /**
     * Redirect the user to the authentication page for the provider.
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function redirect()
    {
        $this->request->session()->put('oauth.temp', $temp = $this->server->get_temporary_credentials());
        return new Redirect_Response($this->server->get_authorization_url($temp));
    }
    /**
     * Get the User instance for the authenticated user.
     *
     * @return \Laravel\Socialite\One\User
     *
     * @throws \Laravel\Socialite\One\MissingVerifierException
     */
    public function user()
    {
        if (!$this->has_necessary_verifier()) {
            throw new Missing_Verifier_Exception('Invalid request. Missing OAuth verifier.');
        }
        $token = $this->get_token();
        $user = $this->server->get_user_details($token, $this->should_bypass_cache($token->get_identifier(), $token->get_secret()));
        $instance = (new User())->set_raw($user->extra)->set_token($token->get_identifier(), $token->get_secret());
        return $instance->map(['id' => $user->uid, 'nickname' => $user->nickname, 'name' => $user->name, 'email' => $user->email, 'avatar' => $user->image_url]);
    }
    /**
     * Get a Social User instance from a known access token and secret.
     *
     * @param  string  $token
     * @param  string  $secret
     * @return \Laravel\Socialite\One\User
     */
    public function user_from_token_and_secret($token, $secret)
    {
        $token_credentials = new Token_Credentials();
        $token_credentials->set_identifier($token);
        $token_credentials->set_secret($secret);
        $user = $this->server->get_user_details($token_credentials, $this->should_bypass_cache($token, $secret));
        $instance = (new User())->set_raw($user->extra)->set_token($token_credentials->get_identifier(), $token_credentials->get_secret());
        return $instance->map(['id' => $user->uid, 'nickname' => $user->nickname, 'name' => $user->name, 'email' => $user->email, 'avatar' => $user->image_url]);
    }
    /**
     * Get the token credentials for the request.
     *
     * @return \League\OAuth1\Client\Credentials\TokenCredentials
     */
    protected function get_token()
    {
        $temp = $this->request->session()->pull('oauth.temp');
        if (!$temp) {
            throw new Missing_Temporary_Credentials_Exception('Missing temporary OAuth credentials.');
        }
        return $this->server->get_token_credentials($temp, $this->request->get('oauth_token'), $this->request->get('oauth_verifier'));
    }
    /**
     * Determine if the request has the necessary OAuth verifier.
     *
     * @return bool
     */
    protected function has_necessary_verifier()
    {
        return $this->request->has(['oauth_token', 'oauth_verifier']);
    }
    /**
     * Determine if the user information cache should be bypassed.
     *
     * @return bool
     */
    protected function should_bypass_cache(string $token, string $secret)
    {
        $new_hash = sha1($token . '_' . $secret);
        if (!empty($this->user_hash) && $new_hash !== $this->user_hash) {
            $this->user_hash = $new_hash;
            return true;
        }
        $this->user_hash = $this->user_hash ?: $new_hash;
        return false;
    }
    /**
     * Set the request instance.
     *
     * @return $this
     */
    public function set_request(Request $request)
    {
        $this->request = $request;
        return $this;
    }
}