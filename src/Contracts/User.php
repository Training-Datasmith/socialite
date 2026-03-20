<?php

declare (strict_types=1);
namespace Laravel\Socialite\Contracts;

interface User
{
    /**
     * Get the unique identifier for the user.
     *
     * @return string
     */
    public function get_id();
    /**
     * Get the nickname / username for the user.
     *
     * @return string|null
     */
    public function get_nickname();
    /**
     * Get the full name of the user.
     *
     * @return string|null
     */
    public function get_name();
    /**
     * Get the e-mail address of the user.
     *
     * @return string|null
     */
    public function get_email();
    /**
     * Get the avatar / image URL for the user.
     *
     * @return string|null
     */
    public function get_avatar();
}