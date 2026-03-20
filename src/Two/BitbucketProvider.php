<?php

declare (strict_types=1);
namespace Laravel\Socialite\Two;

use Exception;
use Guzzle_Http\Request_Options;
use Illuminate\Support\Arr;
class Bitbucket_Provider extends Abstract_Provider implements Provider_Interface
{
    /**
     * The scopes being requested.
     *
     * @var array
     */
    protected $scopes = ['email'];
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
        return $this->build_auth_url_from_base('https://bitbucket.org/site/oauth2/authorize', $state);
    }
    /**
     * {@inheritdoc}
     */
    protected function get_token_url(): string
    {
        return 'https://bitbucket.org/site/oauth2/access_token';
    }
    /**
     * {@inheritdoc}
     */
    protected function get_user_by_token($token)
    {
        $response = $this->get_http_client()->get('https://api.bitbucket.org/2.0/user', [Request_Options::HEADERS => ['Authorization' => 'Bearer ' . $token]]);
        $user = json_decode($response->get_body(), true);
        if (in_array('email', $this->scopes, true)) {
            $user['email'] = $this->get_email_by_token($token);
        }
        return $user;
    }
    /**
     * Get the email for the given access token.
     *
     * @return string|null
     */
    protected function get_email_by_token(string $token)
    {
        $emails_url = 'https://api.bitbucket.org/2.0/user/emails';
        try {
            $response = $this->get_http_client()->get($emails_url, [Request_Options::HEADERS => ['Authorization' => 'Bearer ' . $token]]);
        } catch (Exception $e) {
            return;
        }
        $emails = json_decode($response->get_body(), true);
        foreach ($emails['values'] as $email) {
            if ($email['type'] === 'email' && $email['is_primary'] && $email['is_confirmed']) {
                return $email['email'];
            }
        }
    }
    /**
     * {@inheritdoc}
     */
    protected function map_user_to_object(array $user)
    {
        return (new User())->set_raw($user)->map(['id' => $user['uuid'], 'nickname' => $user['username'], 'name' => Arr::get($user, 'display_name'), 'email' => Arr::get($user, 'email'), 'avatar' => Arr::get($user, 'links.avatar.href')]);
    }
    /**
     * Get the access token for the given code.
     *
     * @param  string  $code
     * @return string
     */
    public function get_access_token($code)
    {
        $response = $this->get_http_client()->post($this->get_token_url(), [Request_Options::AUTH => [$this->client_id, $this->client_secret], Request_Options::HEADERS => ['Accept' => 'application/json'], Request_Options::FORM_PARAMS => $this->get_token_fields($code)]);
        return json_decode($response->get_body(), true)['access_token'];
    }
}