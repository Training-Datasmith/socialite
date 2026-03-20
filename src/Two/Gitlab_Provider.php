<?php

declare (strict_types=1);
namespace Laravel\Socialite\Two;

use Guzzle_Http\Request_Options;
class Gitlab_Provider extends Abstract_Provider implements Provider_Interface
{
    /**
     * The scopes being requested.
     *
     * @var array
     */
    protected $scopes = ['read_user'];
    /**
     * The separating character for the requested scopes.
     *
     * @var string
     */
    protected $scope_separator = ' ';
    /**
     * The Gitlab instance host.
     *
     * @var string
     */
    protected $host = 'https://gitlab.com';
    /**
     * Set the Gitlab instance host.
     *
     * @param  string|null  $host
     * @return $this
     */
    public function set_host($host): self
    {
        if (!empty($host)) {
            $this->host = rtrim($host, '/');
        }
        return $this;
    }
    /**
     * {@inheritdoc}
     */
    protected function get_auth_url($state)
    {
        return $this->build_auth_url_from_base($this->host . '/oauth/authorize', $state);
    }
    /**
     * {@inheritdoc}
     */
    protected function get_token_url(): string
    {
        return $this->host . '/oauth/token';
    }
    /**
     * {@inheritdoc}
     */
    protected function get_user_by_token($token)
    {
        $response = $this->get_http_client()->get($this->host . '/api/v3/user', [Request_Options::HEADERS => ['Authorization' => 'Bearer ' . $token]]);
        return json_decode($response->get_body(), true);
    }
    /**
     * {@inheritdoc}
     */
    protected function map_user_to_object(array $user)
    {
        return (new User())->set_raw($user)->map(['id' => $user['id'], 'nickname' => $user['username'], 'name' => $user['name'], 'email' => $user['email'], 'avatar' => $user['avatar_url']]);
    }
}