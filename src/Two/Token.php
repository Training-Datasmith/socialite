<?php

declare (strict_types=1);
namespace Laravel\Socialite\Two;

class Token
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
     * Create a new token instance.
     */
    public function __construct(string $token, string $refresh_token, int $expires_in, array $approved_scopes)
    {
        $this->token = $token;
        $this->refresh_token = $refresh_token;
        $this->expires_in = $expires_in;
        $this->approved_scopes = $approved_scopes;
    }
}