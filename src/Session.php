<?php

namespace Bredala\Http;

use SessionHandlerInterface;

/**
 * Session
 */
class Session
{
    protected $started = false;
    protected $cache   = '__cache__';

    // -------------------------------------------------------------------------

    /**
     * @param SessionHandlerInterface $handler
     * @param array $options name, lifetime, path, domain, secure, httponly, samesite
     */
    public function __construct(?SessionHandlerInterface $handler = null, array $options = [])
    {
        if ($handler) {
            session_set_save_handler($handler, TRUE);
        }

        $options += [
            'name'     => 'PHPSESSID',
            'lifetime' => 7200,
            'path'     => '/',
            'domain'   => '',
            'secure'   => false,
            'httponly' => true,
            'samesite' => 'Lax',
        ];

        session_name($options['name']);
        session_set_cookie_params([
            'lifetime' => (int) $options['lifetime'],
            'path'     => $options['path'],
            'domain'   => $options['domain'],
            'secure'   => (bool) $options['secure'],
            'httponly' => (bool) $options['httponly'],
            'samesite' => $options['samesite'],
        ]);

        // Cookie lifetime 0 = until browser closes: keep PHP default gc lifetime
        if ($options['lifetime'] > 0) {
            ini_set('session.gc_maxlifetime', (string) $options['lifetime']);
        }
    }

    // -------------------------------------------------------------------------

    /**
     * Start session
     * Handles temporary variables
     * Mark flash data for deletion, and clear old data
     *
     * @return static
     */
    public function start(?string $id = null): static
    {
        if ($this->started) {
            return $this;
        }

        $this->started = true;

        if ($id) {
            session_id($id);
        }
        session_start();

        // Nothing to do
        if (empty($_SESSION[$this->cache])) {
            return $this;
        }

        // Current time
        $now = time();

        foreach ($_SESSION[$this->cache] as $name => $value) {
            if (!isset($_SESSION[$name])) {
                unset($_SESSION[$this->cache][$name]);
            } elseif ($value === 'new') {
                $_SESSION[$this->cache][$name] = 'old';
            } elseif ($value === 'old' || $value < $now) {
                unset($_SESSION[$name], $_SESSION[$this->cache][$name]);
            }
        }

        if (empty($_SESSION[$this->cache])) {
            unset($_SESSION[$this->cache]);
        }

        return $this;
    }

    // -------------------------------------------------------------------------

    /**
     * Return session ID
     *
     * @return string|null
     */
    public function id(): ?string
    {
        if ($this->started) {
            return session_id() ?: null;
        }

        return null;
    }

    /**
     * Regenerate session ID, keeping data
     * Call after login / privilege change to prevent session fixation
     *
     * @param bool $deleteOld Delete the old session file
     * @return static
     */
    public function regenerate(bool $deleteOld = true): static
    {
        if ($this->started) {
            session_regenerate_id($deleteOld);
        }

        return $this;
    }

    /**
     * Get all
     *
     * @return array
     */
    public function all()
    {
        return $_SESSION;
    }

    /**
     * Has data
     *
     * @param string $name
     * @return bool
     */
    public function has(string $name): bool
    {
        return isset($_SESSION[$name]);
    }

    /**
     * Get data
     *
     * @param string $name
     * @param mixed $default
     * @return mixed
     */
    public function get(string $name, mixed $default = null): mixed
    {
        return isset($_SESSION[$name]) ? $_SESSION[$name] : $default;
    }

    /**
     * Set
     *
     * @param string $name
     * @param mixed $value
     * @return static
     */
    public function set(string $name, mixed $value): static
    {
        $_SESSION[$name] = $value;
        return $this;
    }

    // -------------------------------------------------------------------------

    /**
     *
     * @param string $name
     * @param mixed $value
     * @return static
     */
    public function setFlash(string $name, mixed $value): static
    {
        return $this->set($name, $value)->markFlash($name);
    }

    /**
     *
     * @param string $name
     * @param mixed $value
     * @param int $time
     * @return static
     */
    public function setTemp(string $name, mixed $value, int $time = 300)
    {
        return $this->set($name, $value)->markTemp($name, $time);
    }

    // -------------------------------------------------------------------------

    /**
     * Mark as flash
     *
     * @param string $name
     * @return static
     */
    public function markFlash(string $name): static
    {
        $this->addCache($name, 'new');
        return $this;
    }

    /**
     * Unmark flash
     *
     * @param string $name
     * @return static
     */
    public function unmarkFlash(string $name)
    {
        $this->delCache($name);
        return $this;
    }

    /**
     * Mark as temp
     *
     * @param string $name
     * @param int $time
     * @return static
     */
    public function markTemp(string $name, int $time = 300)
    {
        if ($time < 2592000) {
            $time += time();
        }
        $this->addCache($name, $time);

        return $this;
    }

    /**
     * Unmark temp
     *
     * @param string $name
     * @return static
     */
    public function unmarkTemp(string $name)
    {
        $this->delCache($name);
        return $this;
    }

    /**
     * Add to cache
     *
     * @param string $name
     * @param int|string $value
     */
    private function addCache(string $name, mixed $value)
    {
        if (isset($_SESSION[$name])) {
            if (!isset($_SESSION[$this->cache])) {
                $_SESSION[$this->cache] = [];
            }
            $_SESSION[$this->cache][$name] = $value;
        }
    }

    /**
     * Delete to cache
     *
     * @param string $name
     */
    private function delCache(string $name)
    {
        if (isset($_SESSION[$this->cache][$name])) {
            unset($_SESSION[$this->cache][$name]);
        }
    }

    // -------------------------------------------------------------------------

    /**
     * Set
     *
     * @param string $name
     * @return static
     */
    public function delete(string $name): static
    {
        if (isset($_SESSION[$name])) {
            unset($_SESSION[$name]);
        }
        return $this;
    }

    /**
     * Removes all sessions vars
     *
     * @return static
     */
    public function reset(): static
    {
        if ($this->started) {
            session_unset();
        }

        return $this;
    }

    /**
     * Destroys session
     *
     * @return static
     */
    public function destroy(): static
    {
        if ($this->started) {
            session_destroy();
        }
        return $this;
    }


    /**
     * Writes and closes current session
     *
     * @return static
     */
    public function close(): static
    {
        if ($this->started) {
            session_write_close();
        }
        return $this;
    }

    // -------------------------------------------------------------------------
}

/* End of file */
