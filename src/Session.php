<?php

namespace Bredala\Http;

/**
 * Session
 */
class Session
{
    protected $started = false;
    protected $cache   = '__cache__';

    // -------------------------------------------------------------------------

    /**
     * @param \SessionHandlerInterface $handler
     */
    public function __construct(\SessionHandlerInterface $handler = NULL)
    {
        if ($handler) {
            session_set_save_handler($handler, TRUE);
        }
    }

    // -------------------------------------------------------------------------

    /**
     * Handle temporary variables
     *
     * Mark flash data for deletion, and clear old data
     *
     * @return $this
     */
    public function start()
    {
        if ($this->started) {
            return $this;
        }

        $this->started = true;
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
     * Has data
     *
     * @param string $name
     * @return bool
     */
    public function has($name)
    {
        return isset($_SESSION[$name]);
    }

    // -------------------------------------------------------------------------

    /**
     * Get data
     *
     * @param string $name
     * @param mixed $default
     * @return mixed
     */
    public function get($name, $default = null)
    {
        return isset($_SESSION[$name]) ? $_SESSION[$name] : $default;
    }

    // -------------------------------------------------------------------------

    /**
     * Get all
     *
     * @return array
     */
    public function all()
    {
        return $_SESSION;
    }

    // -------------------------------------------------------------------------

    /**
     * Set
     *
     * @param string $name
     * @param mixed $value
     * @return $this
     */
    public function set($name, $value)
    {
        $_SESSION[$name] = $value;
        return $this;
    }

    // -------------------------------------------------------------------------

    /**
     *
     * @param string $name
     * @param mixed $value
     * @return $this
     */
    public function setFlash($name, $value)
    {
        return $this->set($name, $value)->markFlash($name);
    }

    /**
     *
     * @param string $name
     * @param mixed $value
     * @param int $time
     * @return $this
     */
    public function setTemp($name, $value, $time = 300)
    {
        return $this->set($name, $value)->markTemp($name, $time);
    }

    // -------------------------------------------------------------------------

    /**
     * Mark as flash
     *
     * @param string $name
     * @return $this
     */
    public function markFlash($name)
    {
        if (is_array($name)) {
            foreach ($name as $n) {
                $this->addCache($n, 'new');
            }
        } else {
            $this->addCache($name, 'new');
        }

        return $this;
    }

    // -------------------------------------------------------------------------

    /**
     * Add to cache
     *
     * @param string $name
     * @param mixed $value
     */
    private function addCache($name, $value)
    {
        if (isset($_SESSION[$name])) {
            if (!isset($_SESSION[$this->cache])) {
                $_SESSION[$this->cache] = [];
            }
            $_SESSION[$this->cache][$name] = $value;
        }
    }

    // -------------------------------------------------------------------------

    /**
     * Unmark flash
     *
     * @param name $name
     * @return $this
     */
    public function unmarkFlash($name)
    {
        $this->delCache($name);
        return $this;
    }

    // -------------------------------------------------------------------------

    /**
     * Mark as temp
     *
     * @param string $name
     * @param int $time
     * @return $this
     */
    public function markTemp($name, $time = 300)
    {
        $now = time();
        if (is_array($name)) {
            foreach ($name as $n => $t) {
                $this->addCache($n, $now + (int) $t);
            }
        } else {
            $this->addCache($name, $now + (int) $time);
        }

        return $this;
    }

    // -------------------------------------------------------------------------

    /**
     * Unmark temp
     *
     * @param string $name
     * @return $this
     */
    public function unmarkTemp($name)
    {
        if (is_array($name)) {
            foreach ($name as $n) {
                $this->delCache($n);
            }
        } else {
            $this->delCache($name);
        }

        return $this;
    }

    // -------------------------------------------------------------------------

    /**
     * Delete to cache
     *
     * @param string $name
     */
    private function delCache($name)
    {
        if (isset($_SESSION[$this->cache][$name])) {
            unset($_SESSION[$this->cache][$name]);
        }
    }

    // -------------------------------------------------------------------------

    /**
     * Set
     *
     * @param type $name
     * @param type $value
     * @return $this
     */
    public function delete($name)
    {
        if (isset($_SESSION[$name])) {
            unset($_SESSION[$name]);
        }
        return $this;
    }

    // -------------------------------------------------------------------------

    /**
     * Remove all sessions vars
     *
     * @return $this
     */
    public function reset()
    {
        if ($this->started) {
            session_unset();
        }

        return $this;
    }

    // -------------------------------------------------------------------------

    /**
     * Destroy session
     *
     * @return $this
     */
    public function destroy()
    {
        if ($this->started) {
            session_destroy();
        }
        return $this;
    }

    // -------------------------------------------------------------------------

    /**
     * Write and close current session
     *
     * @return $this
     */
    public function close()
    {
        if ($this->started) {
            session_write_close();
        }
        return $this;
    }

    // -------------------------------------------------------------------------
}

/* End of file */
