<?php

declare (strict_types=1);
namespace Laravel\Socialite\Two;

use Guzzle_Http\Request_Options;
use Illuminate\Support\Arr;
class Slack_Provider extends Abstract_Provider implements Provider_Interface
{
    /**
     * The scopes being requested.
     *
     * @var array
     */
    protected $scopes = ['identity.basic', 'identity.email', 'identity.team', 'identity.avatar'];
    /**
     * The key used for scopes.
     *
     * @var string
     */
    protected $scope_key = 'user_scope';
    /**
     * Indicate that the requested token should be for a bot user.
     *
     * @return $this
     */
    public function as_bot_user(): self
    {
        $this->scope_key = 'scope';
        return $this;
    }
    /**
     * {@inheritdoc}
     */
    public function get_auth_url($state)
    {
        return $this->build_auth_url_from_base('https://slack.com/oauth/v2/authorize', $state);
    }
    /**
     * {@inheritdoc}
     */
    protected function get_token_url(): string
    {
        return 'https://slack.com/api/oauth.v2.access';
    }
    /**
     * {@inheritdoc}
     */
    protected function get_user_by_token($token)
    {
        $response = $this->get_http_client()->get('https://slack.com/api/users.identity', [Request_Options::HEADERS => ['Authorization' => 'Bearer ' . $token]]);
        return json_decode($response->get_body(), true);
    }
    /**
     * {@inheritdoc}
     */
    protected function map_user_to_object(array $user)
    {
        return (new User())->set_raw($user)->map(['id' => Arr::get($user, 'user.id'), 'name' => Arr::get($user, 'user.name'), 'email' => Arr::get($user, 'user.email'), 'avatar' => Arr::get($user, 'user.image_512'), 'organization_id' => Arr::get($user, 'team.id')]);
    }
    /**
     * {@inheritdoc}
     */
    protected function get_code_fields($state = null)
    {
        $fields = parent::get_code_fields($state);
        if ($this->scope_key === 'user_scope') {
            $fields['scope'] = '';
            $fields['user_scope'] = $this->format_scopes($this->scopes, $this->scope_separator);
        }
        return $fields;
    }
    /**
     * {@inheritdoc}
     */
    public function get_access_token_response($code)
    {
        $response = $this->get_http_client()->post($this->get_token_url(), [Request_Options::HEADERS => $this->get_token_headers($code), Request_Options::FORM_PARAMS => $this->get_token_fields($code)]);
        $result = json_decode($response->get_body(), true);
        if ($this->scope_key === 'user_scope') {
            return $result['authed_user'];
        }
        return $result;
    }
}