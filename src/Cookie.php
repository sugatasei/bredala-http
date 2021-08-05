<?php

namespace Bredala\Http;

/**
 * Description of Cookie
 */
class Cookie
{
    private array $config;

    // -------------------------------------------------------------------------

    public function __construct(array $config = [])
    {
        $this->config = $config + [
            'prefix'   => '',
            'domain'   => '',
            'path'     => '/',
            'secure'   => false,
            'httponly' => true
        ];
    }

    // -------------------------------------------------------------------------

    /**
     * Fetch an item from the COOKIE array
     *
     * @param string $name
     * @param mixed $default
     * @return mixed
     */
    public function get(string $name, $default = null)
    {
        return $_COOKIE[$this->config['prefix'] . $name] ?? $default;
    }

    /**
     * Create a new cookie
     *
     * @param string $name
     * @param mixed $value
     * @param int $expire
     * @return $this
     */
    public function set(string $name, $value, int $expire)
    {
        // Delete cookie
        if ($expire < 1) {
            $expire = time() - 86500;
        }
        // Absolute & relative expiration date
        else {
            $now  = time();
            $expire = $expire >= $now ? $expire : $now + $expire;
        }

        setcookie(
            $this->config['prefix'] . $name,
            $value,
            $expire,
            $this->config['path'],
            $this->config['domain'],
            $this->config['secure'],
            $this->config['httponly']
        );

        return $this;
    }

    /**
     * Create a new cookie for ever
     *
     * @param string $name
     * @param mixed $value
     * @return $this
     */
    public function forever(string $name, $value)
    {
        return $this->set($name, $value, strtotime('+1 year'));
    }

    /**
     * Delete a cookie
     *
     * @param string $name
     * @return $this
     */
    public function delete(string $name)
    {
        return $this->set($name, '', -1);
    }

    // -------------------------------------------------------------------------
}

/* End of file */
