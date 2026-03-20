<?php

declare (strict_types=1);
namespace Laravel\Socialite\One;

class Twitter_Provider extends Abstract_Provider
{
    /**
     * {@inheritdoc}
     */
    public function user()
    {
        if (!$this->has_necessary_verifier()) {
            throw new Missing_Verifier_Exception('Invalid request. Missing OAuth verifier.');
        }
        $user = $this->server->get_user_details($token = $this->get_token(), $this->should_bypass_cache($token->get_identifier(), $token->get_secret()));
        $extra_details = ['location' => $user->location, 'description' => $user->description];
        $instance = (new User())->set_raw(array_merge($user->extra, $user->urls, $extra_details))->set_token($token->get_identifier(), $token->get_secret());
        return $instance->map(['id' => $user->uid, 'nickname' => $user->nickname, 'name' => $user->name, 'email' => $user->email, 'avatar' => $user->image_url, 'avatar_original' => str_replace('_normal', '', $user->image_url)]);
    }
    /**
     * Set the access level the application should request to the user account.
     */
    public function scope(string $scope): void
    {
        $this->server->set_application_scope($scope);
    }
}