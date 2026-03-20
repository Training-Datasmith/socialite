<?php

declare (strict_types=1);
namespace Laravel\Socialite;

use ArrayAccess;
use Laravel\Socialite\Contracts\User;
abstract class Abstract_User implements ArrayAccess, User
{
    /**
     * The unique identifier for the user.
     *
     * @var mixed
     */
    public $id;
    /**
     * The user's nickname / username.
     *
     * @var string
     */
    public $nickname;
    /**
     * The user's full name.
     *
     * @var string
     */
    public $name;
    /**
     * The user's e-mail address.
     *
     * @var string
     */
    public $email;
    /**
     * The user's avatar image URL.
     *
     * @var string
     */
    public $avatar;
    /**
     * The user's raw attributes.
     *
     * @var array
     */
    public $user;
    /**
     * The user's other attributes.
     *
     * @var array
     */
    public $attributes = [];
    /**
     * Get the unique identifier for the user.
     *
     * @return string
     */
    public function get_id()
    {
        return $this->id;
    }
    /**
     * Get the nickname / username for the user.
     *
     * @return string|null
     */
    public function get_nickname()
    {
        return $this->nickname;
    }
    /**
     * Get the full name of the user.
     *
     * @return string|null
     */
    public function get_name()
    {
        return $this->name;
    }
    /**
     * Get the e-mail address of the user.
     *
     * @return string|null
     */
    public function get_email()
    {
        return $this->email;
    }
    /**
     * Get the avatar / image URL for the user.
     *
     * @return string|null
     */
    public function get_avatar()
    {
        return $this->avatar;
    }
    /**
     * Get the raw user array.
     *
     * @return array
     */
    public function get_raw()
    {
        return $this->user;
    }
    /**
     * Set the raw user array from the provider.
     *
     * @return $this
     */
    public function set_raw(array $user)
    {
        $this->user = $user;
        return $this;
    }
    /**
     * Map the given array onto the user's properties.
     *
     * @return $this
     */
    public function map(array $attributes)
    {
        $this->attributes = $attributes;
        foreach ($attributes as $key => $value) {
            if (property_exists($this, $key)) {
                $this->{$key} = $value;
            }
        }
        return $this;
    }
    /**
     * Determine if the given raw user attribute exists.
     *
     * @param  string  $offset
     * @return bool
     */
    #[\Return_Type_Will_Change]
    public function offsetExists($offset)
    {
        return array_key_exists($offset, $this->user);
    }
    /**
     * Get the given key from the raw user.
     *
     * @param  string  $offset
     * @return mixed
     */
    #[\Return_Type_Will_Change]
    public function offsetGet($offset)
    {
        return $this->user[$offset];
    }
    /**
     * Set the given attribute on the raw user array.
     *
     * @param  string  $offset
     * @param  mixed  $value
     */
    #[\Return_Type_Will_Change]
    public function offsetSet($offset, $value): void
    {
        $this->user[$offset] = $value;
    }
    /**
     * Unset the given value from the raw user array.
     *
     * @param  string  $offset
     */
    #[\Return_Type_Will_Change]
    public function offsetUnset($offset): void
    {
        unset($this->user[$offset]);
    }
    /**
     * Get a user attribute value dynamically.
     *
     * @return void
     */
    public function __get(string $key)
    {
        return $this->attributes[$key] ?? null;
    }
}