<?php

declare (strict_types=1);
namespace Laravel\Socialite\Two;

use Exception;
use Firebase\JWT\JWK;
use Firebase\JWT\JWT;
use Guzzle_Http\Request_Options;
use Illuminate\Support\Arr;
class Google_Provider extends Abstract_Provider implements Provider_Interface
{
    /**
     * The separating character for the requested scopes.
     *
     * @var string
     */
    protected $scope_separator = ' ';
    /**
     * The scopes being requested.
     *
     * @var array
     */
    protected $scopes = ['openid', 'profile', 'email'];
    /**
     * {@inheritdoc}
     */
    protected function get_auth_url($state)
    {
        return $this->build_auth_url_from_base('https://accounts.google.com/o/oauth2/auth', $state);
    }
    /**
     * {@inheritdoc}
     */
    protected function get_token_url(): string
    {
        return 'https://www.googleapis.com/oauth2/v4/token';
    }
    /**
     * {@inheritdoc}
     */
    protected function get_user_by_token($token)
    {
        if ($this->is_jwt_token($token)) {
            return $this->get_user_from_jwt_token($token);
        }
        $response = $this->get_http_client()->get('https://www.googleapis.com/oauth2/v3/userinfo', [Request_Options::QUERY => ['prettyPrint' => 'false'], Request_Options::HEADERS => ['Accept' => 'application/json', 'Authorization' => 'Bearer ' . $token]]);
        return json_decode((string) $response->get_body(), true);
    }
    /**
     * {@inheritdoc}
     */
    public function refresh_token($refresh_token): \Laravel\Socialite\Two\Token
    {
        $response = $this->get_refresh_token_response($refresh_token);
        return new Token(Arr::get($response, 'access_token'), Arr::get($response, 'refresh_token', $refresh_token), Arr::get($response, 'expires_in'), explode($this->scope_separator, Arr::get($response, 'scope', '')));
    }
    /**
     * {@inheritdoc}
     */
    protected function map_user_to_object(array $user)
    {
        // Deprecated: Fields added to keep backwards compatibility in 4.0. These will be removed in 5.0
        $user['id'] = Arr::get($user, 'sub');
        $user['verified_email'] = Arr::get($user, 'email_verified');
        $user['link'] = Arr::get($user, 'profile');
        return (new User())->set_raw($user)->map(['id' => Arr::get($user, 'sub'), 'nickname' => Arr::get($user, 'nickname'), 'name' => Arr::get($user, 'name'), 'email' => Arr::get($user, 'email'), 'avatar' => $avatar_url = Arr::get($user, 'picture'), 'avatar_original' => $avatar_url]);
    }
    /**
     * Determine if the given token is a JWT (ID token).
     *
     * @param  string  $token
     */
    protected function is_jwt_token($token): bool
    {
        return substr_count($token, '.') === 2 && strlen($token) > 100;
    }
    /**
     * Get user data from Google ID token (JWT).
     *
     * @param  string  $idToken
     * @return array
     *
     * @throws \Exception
     */
    protected function get_user_from_jwt_token($id_token)
    {
        try {
            $user = (array) JWT::decode($id_token, JWK::parse_key_set($this->get_google_jwks()));
            if (!isset($user['iss']) || $user['iss'] !== 'https://accounts.google.com') {
                throw new Exception('Invalid ID token issuer.');
            }
            if (!isset($user['aud']) || $user['aud'] !== $this->client_id) {
                throw new Exception('Invalid ID token audience.');
            }
            return $user;
        } catch (Exception $e) {
            throw new Exception('Failed to verify Google JWT token: ' . $e->get_message());
        }
    }
    /**
     * Get Google's JSON Web Key Set for JWT verification.
     *
     * @return array
     */
    protected function get_google_jwks()
    {
        $response = $this->get_http_client()->get('https://www.googleapis.com/oauth2/v3/certs');
        return json_decode((string) $response->get_body(), true);
    }
}