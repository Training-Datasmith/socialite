<?php

declare (strict_types=1);
namespace Laravel\Socialite\Two;

use Guzzle_Http\Request_Options;
use Illuminate\Support\Arr;
class Linked_In_Provider extends Abstract_Provider implements Provider_Interface
{
    /**
     * The scopes being requested.
     *
     * @var array
     */
    protected $scopes = ['r_liteprofile', 'r_emailaddress'];
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
    protected function get_user_by_token($token): array
    {
        $basic_profile = $this->get_basic_profile($token);
        $email_address = $this->get_email_address($token);
        return array_merge($basic_profile, $email_address);
    }
    /**
     * Get the basic profile fields for the user.
     */
    protected function get_basic_profile(string $token): array
    {
        $fields = ['id', 'firstName', 'lastName', 'profilePicture(displayImage~:playableStreams)'];
        if (in_array('r_liteprofile', $this->get_scopes())) {
            array_push($fields, 'vanityName');
        }
        $response = $this->get_http_client()->get('https://api.linkedin.com/v2/me', [Request_Options::HEADERS => ['Authorization' => 'Bearer ' . $token, 'X-RestLi-Protocol-Version' => '2.0.0'], Request_Options::QUERY => ['projection' => '(' . implode(',', $fields) . ')']]);
        return (array) json_decode($response->get_body(), true);
    }
    /**
     * Get the email address for the user.
     */
    protected function get_email_address(string $token): array
    {
        $response = $this->get_http_client()->get('https://api.linkedin.com/v2/emailAddress', [Request_Options::HEADERS => ['Authorization' => 'Bearer ' . $token, 'X-RestLi-Protocol-Version' => '2.0.0'], Request_Options::QUERY => ['q' => 'members', 'projection' => '(elements*(handle~))']]);
        return (array) Arr::get((array) json_decode($response->get_body(), true), 'elements.0.handle~');
    }
    /**
     * {@inheritdoc}
     */
    protected function map_user_to_object(array $user)
    {
        $preferred_locale = Arr::get($user, 'firstName.preferredLocale.language') . '_' . Arr::get($user, 'firstName.preferredLocale.country');
        $first_name = Arr::get($user, 'firstName.localized.' . $preferred_locale);
        $last_name = Arr::get($user, 'lastName.localized.' . $preferred_locale);
        $images = (array) Arr::get($user, 'profilePicture.displayImage~.elements', []);
        $avatar = Arr::first($images, function (array $image): bool {
            return ($image['data']['com.linkedin.digitalmedia.mediaartifact.StillImage']['storageSize']['width'] ?? $image['data']['com.linkedin.digitalmedia.mediaartifact.StillImage']['displaySize']['width']) === 100;
        });
        $original_avatar = Arr::first($images, function (array $image): bool {
            return ($image['data']['com.linkedin.digitalmedia.mediaartifact.StillImage']['storageSize']['width'] ?? $image['data']['com.linkedin.digitalmedia.mediaartifact.StillImage']['displaySize']['width']) === 800;
        });
        return (new User())->set_raw($user)->map(['id' => $user['id'], 'nickname' => null, 'name' => $first_name . ' ' . $last_name, 'first_name' => $first_name, 'last_name' => $last_name, 'email' => Arr::get($user, 'emailAddress'), 'avatar' => Arr::get($avatar, 'identifiers.0.identifier'), 'avatar_original' => Arr::get($original_avatar, 'identifiers.0.identifier')]);
    }
}