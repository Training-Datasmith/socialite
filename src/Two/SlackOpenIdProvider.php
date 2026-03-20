<?php

declare (strict_types=1);
namespace Laravel\Socialite\Two;

use Guzzle_Http\Request_Options;
use Illuminate\Support\Arr;
class Slack_Open_Id_Provider extends Abstract_Provider implements Provider_Interface
{
    /**
     * The scopes being requested.
     *
     * @var array
     */
    protected $scopes = ['openid', 'email', 'profile'];
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
        return $this->build_auth_url_from_base('https://slack.com/openid/connect/authorize', $state);
    }
    /**
     * {@inheritdoc}
     */
    protected function get_token_url(): string
    {
        return 'https://slack.com/api/openid.connect.token';
    }
    /**
     * {@inheritdoc}
     */
    protected function get_user_by_token($token)
    {
        $response = $this->get_http_client()->get('https://slack.com/api/openid.connect.userInfo', [Request_Options::HEADERS => ['Authorization' => 'Bearer ' . $token]]);
        return json_decode($response->get_body(), true);
    }
    /**
     * {@inheritdoc}
     */
    protected function map_user_to_object(array $user)
    {
        return (new User())->set_raw($user)->map(['id' => Arr::get($user, 'sub'), 'nickname' => null, 'name' => Arr::get($user, 'name'), 'email' => Arr::get($user, 'email'), 'avatar' => Arr::get($user, 'picture'), 'organization_id' => Arr::get($user, 'https://slack.com/team_id')]);
    }
}