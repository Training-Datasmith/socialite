<?php

declare (strict_types=1);
namespace Laravel\Socialite\One;

use Laravel\Socialite\Abstract_User;
class User extends Abstract_User
{
    /**
     * The user's access token.
     *
     * @var string
     */
    public $token;
    /**
     * The user's access token secret.
     *
     * @var string
     */
    public $token_secret;
    /**
     * Set the token on the user.
     *
     * @param  string  $token
     * @param  string  $tokenSecret
     * @return $this
     */
    public function set_token($token, $token_secret): self
    {
        $this->token = $token;
        $this->token_secret = $token_secret;
        return $this;
    }
}