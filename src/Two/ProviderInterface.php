<?php

declare (strict_types=1);
namespace Laravel\Socialite\Two;

interface Provider_Interface
{
    /**
     * Redirect the user to the authentication page for the provider.
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function redirect();
    /**
     * Get the User instance for the authenticated user.
     *
     * @return \Laravel\Socialite\Two\User
     */
    public function user();
}