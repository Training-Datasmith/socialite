<?php

declare (strict_types=1);
namespace Laravel\Socialite\Two;

use Guzzle_Http\Client;
use Guzzle_Http\Request_Options;
use Illuminate\Http\Redirect_Response;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Laravel\Socialite\Contracts\Provider as ProviderContract;
abstract class Abstract_Provider implements Provider_Contract
{
    /**
     * The HTTP request instance.
     *
     * @var \Illuminate\Http\Request
     */
    protected $request;
    /**
     * The HTTP Client instance.
     *
     * @var \GuzzleHttp\Client
     */
    protected $http_client;
    /**
     * The client ID.
     *
     * @var string
     */
    protected $client_id;
    /**
     * The client secret.
     *
     * @var string
     */
    protected $client_secret;
    /**
     * The redirect URL.
     *
     * @var string
     */
    protected $redirect_url;
    /**
     * The custom parameters to be sent with the request.
     *
     * @var array
     */
    protected $parameters = [];
    /**
     * The scopes being requested.
     *
     * @var array
     */
    protected $scopes = [];
    /**
     * The separating character for the requested scopes.
     *
     * @var string
     */
    protected $scope_separator = ',';
    /**
     * The type of the encoding in the query.
     *
     * @var int Can be either PHP_QUERY_RFC3986 or PHP_QUERY_RFC1738.
     */
    protected $encoding_type = PHP_QUERY_RFC1738;
    /**
     * Indicates if the session state should be utilized.
     *
     * @var bool
     */
    protected $stateless = false;
    /**
     * Indicates if PKCE should be used.
     *
     * @var bool
     */
    protected $uses_pkce = false;
    /**
     * The custom Guzzle configuration options.
     *
     * @var array
     */
    protected $guzzle = [];
    /**
     * The cached user instance.
     *
     * @var \Laravel\Socialite\Two\User|null
     */
    protected $user;
    /**
     * Create a new provider instance.
     *
     * @param  string  $clientId
     * @param  string  $clientSecret
     * @param  string  $redirectUrl
     * @param  array  $guzzle
     */
    public function __construct(Request $request, $client_id, $client_secret, $redirect_url, $guzzle = [])
    {
        $this->guzzle = $guzzle;
        $this->request = $request;
        $this->client_id = $client_id;
        $this->redirect_url = $redirect_url;
        $this->client_secret = $client_secret;
    }
    /**
     * Get the authentication URL for the provider.
     *
     * @param  string  $state
     * @return string
     */
    abstract protected function get_auth_url($state);
    /**
     * Get the token URL for the provider.
     *
     * @return string
     */
    abstract protected function get_token_url();
    /**
     * Get the raw user for the given access token.
     *
     * @param  string  $token
     * @return mixed
     */
    abstract protected function get_user_by_token($token);
    /**
     * Map the raw user array to a Socialite User instance.
     *
     * @return \Laravel\Socialite\Two\User
     */
    abstract protected function map_user_to_object(array $user);
    /**
     * Redirect the user of the application to the provider's authentication screen.
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function redirect()
    {
        $state = null;
        if ($this->uses_state()) {
            $this->request->session()->put('state', $state = $this->get_state());
        }
        if ($this->uses_pkce()) {
            $this->request->session()->put('code_verifier', $this->get_code_verifier());
        }
        return new Redirect_Response($this->get_auth_url($state));
    }
    /**
     * Build the authentication URL for the provider from the given base URL.
     *
     * @param  string  $state
     * @return string
     */
    protected function build_auth_url_from_base(string $url, $state)
    {
        return $url . '?' . http_build_query($this->get_code_fields($state), '', '&', $this->encoding_type);
    }
    /**
     * Get the GET parameters for the code request.
     *
     * @param  string|null  $state
     * @return array
     */
    protected function get_code_fields($state = null)
    {
        $fields = ['client_id' => $this->client_id, 'redirect_uri' => $this->redirect_url, 'scope' => $this->format_scopes($this->get_scopes(), $this->scope_separator), 'response_type' => 'code'];
        if ($this->uses_state()) {
            $fields['state'] = $state;
        }
        if ($this->uses_pkce()) {
            $fields['code_challenge'] = $this->get_code_challenge();
            $fields['code_challenge_method'] = $this->get_code_challenge_method();
        }
        return array_merge($fields, $this->parameters);
    }
    /**
     * Format the given scopes.
     *
     * @param  string  $scopeSeparator
     * @return string
     */
    protected function format_scopes(array $scopes, $scope_separator)
    {
        return implode($scope_separator, $scopes);
    }
    /**
     * {@inheritdoc}
     */
    public function user()
    {
        if ($this->user) {
            return $this->user;
        }
        if ($this->has_invalid_state()) {
            throw new Invalid_State_Exception();
        }
        $response = $this->get_access_token_response($this->get_code());
        $user = $this->get_user_by_token(Arr::get($response, 'access_token'));
        return $this->user_instance($response, $user);
    }
    /**
     * Create a user instance from the given data.
     *
     * @return \Laravel\Socialite\Two\User
     */
    protected function user_instance(array $response, array $user)
    {
        $this->user = $this->map_user_to_object($user);
        return $this->user->set_token(Arr::get($response, 'access_token'))->set_refresh_token(Arr::get($response, 'refresh_token'))->set_expires_in(Arr::get($response, 'expires_in'))->set_approved_scopes(explode($this->scope_separator, Arr::get($response, 'scope', '')));
    }
    /**
     * Get a Social User instance from a known access token.
     *
     * @param  string  $token
     * @return \Laravel\Socialite\Two\User
     */
    public function user_from_token($token)
    {
        $user = $this->map_user_to_object($this->get_user_by_token($token));
        return $user->set_token($token);
    }
    /**
     * Determine if the current request / session has a mismatching "state".
     *
     * @return bool
     */
    protected function has_invalid_state()
    {
        if ($this->is_stateless()) {
            return false;
        }
        $state = $this->request->session()->pull('state');
        return empty($state) || $this->request->input('state') !== $state;
    }
    /**
     * Get the access token response for the given code.
     *
     * @param  string  $code
     * @return mixed
     */
    public function get_access_token_response($code)
    {
        $response = $this->get_http_client()->post($this->get_token_url(), [Request_Options::HEADERS => $this->get_token_headers($code), Request_Options::FORM_PARAMS => $this->get_token_fields($code)]);
        return json_decode($response->get_body(), true);
    }
    /**
     * Get the headers for the access token request.
     *
     * @param  string  $code
     * @return array
     */
    protected function get_token_headers($code)
    {
        return ['Accept' => 'application/json'];
    }
    /**
     * Get the POST fields for the token request.
     *
     * @param  string  $code
     * @return array
     */
    protected function get_token_fields($code)
    {
        $fields = ['grant_type' => 'authorization_code', 'client_id' => $this->client_id, 'client_secret' => $this->client_secret, 'code' => $code, 'redirect_uri' => $this->redirect_url];
        if ($this->uses_pkce()) {
            $fields['code_verifier'] = $this->request->session()->pull('code_verifier');
        }
        return array_merge($fields, $this->parameters);
    }
    /**
     * Refresh a user's access token with a refresh token.
     *
     * @param  string  $refreshToken
     * @return \Laravel\Socialite\Two\Token
     */
    public function refresh_token($refresh_token)
    {
        $response = $this->get_refresh_token_response($refresh_token);
        return new Token(Arr::get($response, 'access_token'), Arr::get($response, 'refresh_token'), Arr::get($response, 'expires_in'), explode($this->scope_separator, Arr::get($response, 'scope', '')));
    }
    /**
     * Get the refresh token response for the given refresh token.
     *
     * @param  string  $refreshToken
     * @return mixed
     */
    protected function get_refresh_token_response($refresh_token)
    {
        return json_decode($this->get_http_client()->post($this->get_token_url(), [Request_Options::HEADERS => ['Accept' => 'application/json'], Request_Options::FORM_PARAMS => ['grant_type' => 'refresh_token', 'refresh_token' => $refresh_token, 'client_id' => $this->client_id, 'client_secret' => $this->client_secret]])->get_body(), true);
    }
    /**
     * Get the code from the request.
     *
     * @return string
     */
    protected function get_code()
    {
        return $this->request->input('code');
    }
    /**
     * Merge the scopes of the requested access.
     *
     * @param  array|string  $scopes
     * @return $this
     */
    public function scopes($scopes)
    {
        $this->scopes = array_values(array_unique(array_merge($this->scopes, (array) $scopes)));
        return $this;
    }
    /**
     * Set the scopes of the requested access.
     *
     * @param  array|string  $scopes
     * @return $this
     */
    public function set_scopes($scopes)
    {
        $this->scopes = array_values(array_unique((array) $scopes));
        return $this;
    }
    /**
     * Get the current scopes.
     *
     * @return array
     */
    public function get_scopes()
    {
        return $this->scopes;
    }
    /**
     * Set the redirect URL.
     *
     * @param  string  $url
     * @return $this
     */
    public function redirect_url($url)
    {
        $this->redirect_url = $url;
        return $this;
    }
    /**
     * Get a instance of the Guzzle HTTP client.
     *
     * @return \GuzzleHttp\Client
     */
    protected function get_http_client()
    {
        if (is_null($this->http_client)) {
            $this->http_client = new Client($this->guzzle);
        }
        return $this->http_client;
    }
    /**
     * Set the Guzzle HTTP client instance.
     *
     * @return $this
     */
    public function set_http_client(Client $client)
    {
        $this->http_client = $client;
        return $this;
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
    /**
     * Determine if the provider is operating with state.
     *
     * @return bool
     */
    protected function uses_state()
    {
        return !$this->stateless;
    }
    /**
     * Determine if the provider is operating as stateless.
     *
     * @return bool
     */
    protected function is_stateless()
    {
        return $this->stateless;
    }
    /**
     * Indicates that the provider should operate as stateless.
     *
     * @return $this
     */
    public function stateless()
    {
        $this->stateless = true;
        return $this;
    }
    /**
     * Get the string used for session state.
     *
     * @return string
     */
    protected function get_state()
    {
        return Str::random(40);
    }
    /**
     * Determine if the provider uses PKCE.
     *
     * @return bool
     */
    protected function uses_pkce()
    {
        return $this->uses_pkce;
    }
    /**
     * Enables PKCE for the provider.
     *
     * @return $this
     */
    public function enable_pkce()
    {
        $this->uses_pkce = true;
        return $this;
    }
    /**
     * Generates a random string of the right length for the PKCE code verifier.
     *
     * @return string
     */
    protected function get_code_verifier()
    {
        return Str::random(96);
    }
    /**
     * Generates the PKCE code challenge based on the PKCE code verifier in the session.
     *
     * @return string
     */
    protected function get_code_challenge()
    {
        $hashed = hash('sha256', $this->request->session()->get('code_verifier'), true);
        return rtrim(strtr(base64_encode($hashed), '+/', '-_'), '=');
    }
    /**
     * Returns the hash method used to calculate the PKCE code challenge.
     *
     * @return string
     */
    protected function get_code_challenge_method()
    {
        return 'S256';
    }
    /**
     * Set the custom parameters of the request.
     *
     * @return $this
     */
    public function with(array $parameters)
    {
        $this->parameters = $parameters;
        return $this;
    }
}