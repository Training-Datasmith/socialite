<?php

declare (strict_types=1);
namespace Laravel\Socialite\Two;

use Guzzle_Http\Request_Options;
class Linked_In_Open_Id_Provider extends Abstract_Provider implements Provider_Interface
{
    /**
     * The scopes being requested.
     *
     * @var array
     */
    protected $scopes = ['openid', 'profile', 'email'];
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
        return $this->build_auth_url_from_base('https://www.linkedin.com/oauth/v2/authorization', $state);
    }
    /**
     * {@inheritdoc}
     */
    protected function get_token_url(): string
    {
        return 'https://www.linkedin.com/oauth/v2/accessToken';
    }
    /**
     * {@inheritdoc}
     */
    protected function get_user_by_token($token)
    {
        return $this->get_basic_profile($token);
    }
    /**
     * Get the basic profile fields for the user.
     */
    protected function get_basic_profile(string $token): array
    {
        $response = $this->get_http_client()->get('https://api.linkedin.com/v2/userinfo', [Request_Options::HEADERS => ['Authorization' => 'Bearer ' . $token, 'X-RestLi-Protocol-Version' => '2.0.0'], Request_Options::QUERY => ['projection' => '(sub,email,email_verified,name,given_name,family_name,picture)']]);
        return (array) json_decode($response->get_body(), true);
    }
    /**
     * {@inheritdoc}
     */
    protected function map_user_to_object(array $user)
    {
        return (new User())->set_raw($user)->map(['id' => $user['sub'], 'nickname' => null, 'name' => $user['name'], 'first_name' => $user['given_name'], 'last_name' => $user['family_name'], 'email' => $user['email'] ?? null, 'email_verified' => $user['email_verified'] ?? null, 'avatar' => $user['picture'] ?? null, 'avatar_original' => $user['picture'] ?? null]);
    }
}