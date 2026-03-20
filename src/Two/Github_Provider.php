<?php

declare (strict_types=1);
namespace Laravel\Socialite\Two;

use Exception;
use Guzzle_Http\Request_Options;
use Illuminate\Support\Arr;
class Github_Provider extends Abstract_Provider implements Provider_Interface
{
    /**
     * The scopes being requested.
     *
     * @var array
     */
    protected $scopes = ['user:email'];
    /**
     * {@inheritdoc}
     */
    protected function get_auth_url($state)
    {
        return $this->build_auth_url_from_base('https://github.com/login/oauth/authorize', $state);
    }
    /**
     * {@inheritdoc}
     */
    protected function get_token_url(): string
    {
        return 'https://github.com/login/oauth/access_token';
    }
    /**
     * {@inheritdoc}
     */
    protected function get_user_by_token($token)
    {
        $user_url = 'https://api.github.com/user';
        $response = $this->get_http_client()->get($user_url, $this->get_request_options($token));
        $user = json_decode($response->get_body(), true);
        if (in_array('user:email', $this->scopes, true)) {
            $user['email'] = $this->get_email_by_token($token);
        }
        return $user;
    }
    /**
     * Get the email for the given access token.
     *
     * @param  string  $token
     * @return string|null
     */
    protected function get_email_by_token($token)
    {
        $emails_url = 'https://api.github.com/user/emails';
        try {
            $response = $this->get_http_client()->get($emails_url, $this->get_request_options($token));
        } catch (Exception $e) {
            return;
        }
        foreach (json_decode($response->get_body(), true) as $email) {
            if ($email['primary'] && $email['verified']) {
                return $email['email'];
            }
        }
    }
    /**
     * {@inheritdoc}
     */
    protected function map_user_to_object(array $user)
    {
        return (new User())->set_raw($user)->map(['id' => $user['id'], 'nodeId' => $user['node_id'], 'nickname' => $user['login'], 'name' => Arr::get($user, 'name'), 'email' => Arr::get($user, 'email'), 'avatar' => $user['avatar_url']]);
    }
    /**
     * Get the default options for an HTTP request.
     */
    protected function get_request_options(string $token): array
    {
        return [Request_Options::HEADERS => ['Accept' => 'application/vnd.github.v3+json', 'Authorization' => 'token ' . $token]];
    }
}