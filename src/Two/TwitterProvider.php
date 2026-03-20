<?php

declare (strict_types=1);
namespace Laravel\Socialite\Two;

use Guzzle_Http\Request_Options;
use Illuminate\Support\Arr;
class Twitter_Provider extends Abstract_Provider implements Provider_Interface
{
    /**
     * The scopes being requested.
     *
     * @var array
     */
    protected $scopes = ['users.read', 'users.email', 'tweet.read'];
    /**
     * Indicates if PKCE should be used.
     *
     * @var bool
     */
    protected $uses_pkce = true;
    /**
     * The separating character for the requested scopes.
     *
     * @var string
     */
    protected $scope_separator = ' ';
    /**
     * The query encoding format.
     *
     * @var int
     */
    protected $encoding_type = PHP_QUERY_RFC3986;
    /**
     * {@inheritdoc}
     */
    public function get_auth_url($state)
    {
        return $this->build_auth_url_from_base('https://twitter.com/i/oauth2/authorize', $state);
    }
    /**
     * {@inheritdoc}
     */
    protected function get_token_url(): string
    {
        return 'https://api.twitter.com/2/oauth2/token';
    }
    /**
     * {@inheritdoc}
     */
    protected function get_user_by_token($token)
    {
        $response = $this->get_http_client()->get('https://api.twitter.com/2/users/me', [Request_Options::HEADERS => ['Authorization' => 'Bearer ' . $token], Request_Options::QUERY => ['user.fields' => 'profile_image_url,confirmed_email']]);
        return Arr::get(json_decode($response->get_body(), true), 'data');
    }
    /**
     * {@inheritdoc}
     */
    protected function map_user_to_object(array $user)
    {
        return (new User())->set_raw($user)->map(['id' => $user['id'], 'email' => $user['confirmed_email'] ?? null, 'nickname' => $user['username'], 'name' => $user['name'], 'avatar' => $user['profile_image_url']]);
    }
    /**
     * {@inheritdoc}
     */
    public function get_access_token_response($code)
    {
        $response = $this->get_http_client()->post($this->get_token_url(), [Request_Options::HEADERS => ['Accept' => 'application/json'], Request_Options::AUTH => [$this->client_id, $this->client_secret], Request_Options::FORM_PARAMS => $this->get_token_fields($code)]);
        return json_decode($response->get_body(), true);
    }
    /**
     * {@inheritdoc}
     */
    protected function get_refresh_token_response($refresh_token)
    {
        $response = $this->get_http_client()->post($this->get_token_url(), [Request_Options::HEADERS => ['Accept' => 'application/json'], Request_Options::AUTH => [$this->client_id, $this->client_secret], Request_Options::FORM_PARAMS => ['grant_type' => 'refresh_token', 'refresh_token' => $refresh_token, 'client_id' => $this->client_id]]);
        return json_decode($response->get_body(), true);
    }
    /**
     * {@inheritdoc}
     */
    protected function get_code_fields($state = null)
    {
        $fields = parent::get_code_fields($state);
        if ($this->is_stateless()) {
            $fields['state'] = 'state';
        }
        return $fields;
    }
}