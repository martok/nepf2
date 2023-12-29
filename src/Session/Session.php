<?php
/**
 * Nepf2 Framework - Session
 *
 * @link       https://github.com/martok/nepf2
 * @copyright  Copyright (c) 2023- Martok & Contributors.
 * @license    Apache License
 */

namespace Nepf2\Session;

use Nepf2\Application;
use Nepf2\Util\Arr;

class Session implements \Nepf2\IComponent
{
    public const ComponentName = "session";
    private Application $app;
    private readonly string $session_name;
    private readonly int $session_lifetime;
    private readonly string $session_path;
    private readonly string $session_domain;
    private readonly string $session_scope;
    private ?array $access = null;
    private string|false $session_id;

    public function __construct(Application $application)
    {
        $this->app = $application;
    }

    /**
     * @param array $config {
     * @type string $name   Cookie name, default: leave unchanged.
     * @type int $lifetime  Cookie lifetime (s), default: 0.
     * @type string $path   Cookie path, default: '/',
     * @type string $domain Cookie domain, default: ''
     * @type string $scope  Variables scope, default: 'Nepf2'
     * }
     * @return void
     */
    public function configure(array $config)
    {
        $config = Arr::ExtendConfig([
            'name' => '',
            'lifetime' => 0,
            'path' => '/',
            'domain' => '',
            'scope' => 'Nepf2'
        ], $config);

        $this->session_name = empty($config['name']) ? session_name() : $config['name'];
        $this->session_lifetime = $config['lifetime'];
        $this->session_path = $config['path'];
        $this->session_domain = $config['domain'];
        $this->session_scope = $config['scope'];

        session_name($this->session_name);
        session_set_cookie_params($this->session_lifetime, $this->session_path, $this->session_domain, false, true);
    }

    public function &__get(string $name): mixed
    {
        $this->ensureOp('read');

        return $this->access[$name];
    }

    public function __set(string $name, mixed $value): void
    {
        $this->ensureOp('write');

        $this->access[$name] = $value;
    }

    public function __isset(string $name): bool
    {
        $this->ensureOp('read');

        return isset($this->access[$name]);
    }

    public function __unset(string $name): void
    {
        $this->ensureOp('write');

        unset($this->access[$name]);
    }

    protected function ensureOp(string $op): void
    {
        switch ($op) {
            case 'read':
                if (!$this->isBound())
                    throw new Exception('Session not started before read');
                break;
            case 'write':
                if (!$this->isActive())
                    throw new Exception('Session not started before read');
        }
    }

    public function isBound(): bool
    {
        return !is_null($this->access);
    }

    public function isActive(): bool
    {
        return PHP_SESSION_ACTIVE === session_status();
    }

    public function start(): void
    {
        if ($this->isActive())
            return;

        // make sure the session cookie contains a valid session ID
        if(isset($_COOKIE[$this->session_name]) && !preg_match('/^[-,a-zA-Z0-9]{22,256}$/', $_COOKIE[$this->session_name])) {
            unset($_COOKIE[$this->session_name]);
        }

        session_cache_limiter('');
        session_start();

        // ensure a clean session namespace
        if (!isset($_SESSION[$this->session_scope]) || !is_array($_SESSION[$this->session_scope]))
            $_SESSION[$this->session_scope] = [];
        $this->access = &$_SESSION[$this->session_scope];
        $this->session_id = session_id();
    }

    public function closeWrite(): void
    {
        session_write_close();
    }

    public function destroy(): void
    {
        if ($this->isActive()) {
            $this->resendCookie(true);

            session_unset();
            session_destroy();
            $_SESSION = array();
            $this->access = null;
        }
    }

    private function resendCookie(bool $destroy): void
    {
        if (isset($_COOKIE[$this->session_name]) && ($_COOKIE[$this->session_name] == $this->session_id)) {
            $params = session_get_cookie_params();
            $time = $destroy ? time() - 42000 : ($params['lifetime'] ? time() + $params['lifetime'] : 0);
            $content = $destroy ? '' : $this->session_id;
            setcookie($this->session_name, $content, $time,
                $params['path'], $params['domain'],
                $params['secure'], $params['httponly']
            );
        }
    }

    public function refreshCookie(): void
    {
        $this->resendCookie(false);
    }

}