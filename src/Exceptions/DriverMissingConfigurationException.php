<?php

declare (strict_types=1);
namespace Laravel\Socialite\Exceptions;

use InvalidArgumentException;
class Driver_Missing_Configuration_Exception extends InvalidArgumentException
{
    /**
     * Create a new exception for a missing configuration.
     *
     * @param  string  $provider
     * @param  array<int, string>  $keys
     * @return static
     */
    public static function make($provider, $keys): self
    {
        /** @phpstan-ignore new.static */
        return new static('Missing required configuration keys [' . implode(', ', $keys) . "] for [{$provider}] OAuth provider.");
    }
}