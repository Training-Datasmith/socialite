<?php

declare (strict_types=1);
namespace Laravel\Socialite\Two;

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
     * The refresh token that can be exchanged for a new access token.
     *
     * @var string
     */
    public $refresh_token;
    /**
     * The number of seconds the access token is valid for.
     *
     * @var int
     */
    public $expires_in;
    /**
     * The scopes the user authorized. The approved scopes may be a subset of the requested scopes.
     *
     * @var array
     */
    public $approved_scopes;
    /**
     * Set the token on the user.
     *
     * @param  string  $token
     * @return $this
     */
    public function set_token($token): self
    {
        $this->token = $token;
        return $this;
    }
    /**
     * Set the refresh token required to obtain a new access token.
     *
     * @param  string  $refreshToken
     * @return $this
     */
    public function set_refresh_token($refresh_token): self
    {
        $this->refresh_token = $refresh_token;
        return $this;
    }
    /**
     * Set the number of seconds the access token is valid for.
     *
     * @param  int  $expiresIn
     * @return $this
     */
    public function set_expires_in($expires_in): self
    {
        $this->expires_in = $expires_in;
        return $this;
    }
    /**
     * Set the scopes that were approved by the user during authentication.
     *
     * @param  array  $approvedScopes
     * @return $this
     */
    public function set_approved_scopes($approved_scopes): self
    {
        $this->approved_scopes = $approved_scopes;
        return $this;
    }
}