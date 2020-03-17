<?php

namespace GigaAI\LightKit;

/**
 * Session Management Class
 *
 * @package GigaAI
 * @since 2.2
 */
class Cookie
{
    /**
     * Get the session value by key
     *
     * @param mixed $key
     * @param string $default
     *
     * @return string
     */
    public static function get($key, $default = '')
    {
        if (array_key_exists($key, $_COOKIE)) {
            $value = $_COOKIE[$key];

            // Value marked as flash
            if (strpos($value, 'FLASH!!', 0) !== false) {
                $value = substr($value, 7, strlen($value));
                self::forget($key);
            }

            return $value;
        }

        return $default;
    }

    /**
     * Check if the session has given key
     *
     * @param $key
     *
     * @return bool
     */
    public static function has($key)
    {
        return array_key_exists($key, $_COOKIE);
    }

    /**
     * Set the session value
     *
     * @param        $key
     * @param string $value
     */
    public static function set($key, $value = '')
    {
        if (!is_array($key)) {
            $key = [$key => $value];
        }

        foreach ($key as $k => $v) {
            setcookie($k, $v, time() + 3600, COOKIEPATH, COOKIE_DOMAIN);
        }
    }

    /**
     * Remove session key
     *
     * @param $key
     */
    public static function forget($key)
    {
        unset($_COOKIE[$key]);
        setcookie($key, '', time() - 3600, COOKIEPATH, COOKIE_DOMAIN);
    }

    /**
     * Session flash. Generate one time session value.
     *
     * @param        $key
     * @param string $value
     */
    public static function flash($key, $value = '')
    {

        if (!is_array($key)) {
            $key = [$key => $value];
        }

        foreach ($key as $k => $v) {
            self::set($k, 'FLASH!!' . $v);
        }
    }
}
