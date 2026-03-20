<?php

declare (strict_types=1);
namespace Laravel\Socialite\Two;

use Guzzle_Http\Request_Options;
use Illuminate\Support\Arr;
class Twitch_Provider extends Abstract_Provider implements Provider_Interface
{
    /**
     * The scopes being requested.
     *
     * @var array
     */
    protected $scopes = ['user:read:email'];
    /**
     * The separating character for the requested scopes.
     *
     * @var string
     */
    protected $scope_separator = ' ';
    /**
     * {@inheritdoc}
     */
    protected function get_auth_url($state)
    {
        return $this->build_auth_url_from_base('https://id.twitch.tv/oauth2/authorize', $state);
    }
    /**
     * {@inheritdoc}
     */
    protected function get_token_url(): string
    {
        return 'https://id.twitch.tv/oauth2/token';
    }
    /**
     * {@inheritdoc}
     */
    protected function get_user_by_token($token)
    {
        $response = $this->get_http_client()->get('https://api.twitch.tv/helix/users', [Request_Options::HEADERS => ['Accept' => 'application/json', 'Authorization' => 'Bearer ' . $token, 'Client-ID' => $this->client_id]]);
        return json_decode((string) $response->get_body(), true);
    }
    /**
     * Create a user instance from the given data.
     *
     * @return \Laravel\Socialite\Two\User
     */
    protected function user_instance(array $response, array $user)
    {
        $this->user = $this->map_user_to_object($user);
        $scopes = Arr::get($response, 'scope', []);
        if (!is_array($scopes)) {
            $scopes = explode($this->scope_separator, $scopes);
        }
        return $this->user->set_token(Arr::get($response, 'access_token'))->set_refresh_token(Arr::get($response, 'refresh_token'))->set_expires_in(Arr::get($response, 'expires_in'))->set_approved_scopes($scopes);
    }
    /**
     * {@inheritdoc}
     */
    protected function map_user_to_object(array $user)
    {
        $user = $user['data']['0'];
        return (new User())->set_raw($user)->map(['id' => $user['id'], 'nickname' => $user['display_name'], 'name' => $user['display_name'], 'email' => Arr::get($user, 'email'), 'avatar' => $user['profile_image_url']]);
    }
    /**
     * {@inheritdoc}
     */
    public function refresh_token($refresh_token): \Laravel\Socialite\Two\Token
    {
        $response = $this->get_refresh_token_response($refresh_token);
        return new Token(Arr::get($response, 'access_token'), Arr::get($response, 'refresh_token'), Arr::get($response, 'expires_in'), Arr::get($response, 'scope', []));
    }
}