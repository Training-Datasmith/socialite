<?php

declare (strict_types=1);
namespace Laravel\Socialite\Two;

use Guzzle_Http\Request_Options;
use Illuminate\Support\Arr;
class X_Provider extends Twitter_Provider
{
    /**
     * {@inheritdoc}
     */
    public function get_auth_url($state)
    {
        return $this->build_auth_url_from_base('https://x.com/i/oauth2/authorize', $state);
    }
    /**
     * {@inheritdoc}
     */
    protected function get_token_url(): string
    {
        return 'https://api.x.com/2/oauth2/token';
    }
    /**
     * {@inheritdoc}
     */
    protected function get_user_by_token($token)
    {
        $response = $this->get_http_client()->get('https://api.x.com/2/users/me', [Request_Options::HEADERS => ['Authorization' => 'Bearer ' . $token], Request_Options::QUERY => ['user.fields' => 'profile_image_url,confirmed_email']]);
        return Arr::get(json_decode($response->get_body(), true), 'data');
    }
}