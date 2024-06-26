<?php
/**
 * Nepf2 Framework
 *
 * @link       https://github.com/martok/nepf2
 * @copyright  Copyright (c) 2023- Martok & Contributors.
 * @license    Apache License
 */

namespace Nepf2;

use Monolog\Handler\FingersCrossedHandler;
use Monolog\Handler\NullHandler;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Nepf2\Router\RouteMatcher;
use Nepf2\Util\Arr;
use Nepf2\Util\ClassUtil;
use Nepf2\Util\Path;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Sabre\HTTP\Sapi;
use TypeError;

class Application
{
    private array $components = [];
    private string $root;
    private array $userConfig = [];
    private array $logChannels = [];
    private Request $activeRequest;
    private Response $activeResponse;
    public readonly bool $isCLI;

    public function __construct()
    {
        $this->root = getcwd();
        $this->isCLI = PHP_SAPI === 'cli';
    }

    public function getRoot(): string
    {
        return $this->root;
    }

    public function setRoot(string $root): void
    {
        $this->root = realpath($root);
    }

    public function expandPath(string $path): string
    {
        return Path::ExpandRelative($this->root, $path);
    }

    /**
     * Load configuration objects from the given (relative) $fileNames
     * and return the recursively merged object.
     *
     * @param array $fileNames List of file names to load.
     * @return array
     */
    public function mergeConfigs(array $fileNames): array
    {
        $importer = \Closure::bind(static function ($file) {
            return include $file;
        }, null, null);
        $result = null;
        foreach ($fileNames as $file) {
            $file = $this->expandPath($file);
            if (file_exists($file)) {
                $fd = $importer($file);
                $result = is_null($result) ? $fd : Arr::ExtendConfig($result, $fd, false);
            }
        }
        return $result;
    }

    /**
     * Store an array reference as a "user provided config".
     * Not used or modified anywhere by Nepf2.
     *
     * @param array $userConfig Array to store
     */
    public function setUserConfig(array $userConfig)
    {
        $this->userConfig = $userConfig;
    }

    /**
     * Return the user config array stored by setUserConfig.
     *
     * @param string $key
     * @return mixed The user config if no key is given, a value from the config otherwise
     */
    public function cfg(string $key = ''): mixed
    {
        if (!$key)
            return $this->userConfig;
        return Arr::DottedAccess($this->userConfig, $key);
    }

    public function setLogConfig(array $config): void
    {
        $config = Util\Arr::ExtendConfig([
            'file' => '',
            'only_errors' => false,
        ], $config);
        $logger = new Logger("default");
        $handler = new NullHandler();
        if (!empty($config['file']))
            $handler = new StreamHandler($this->expandPath($config['file']));
        if ((bool)$config['only_errors'])
            $handler = new FingersCrossedHandler($handler);
        $logger->pushHandler($handler);
        $this->logChannels[null] = $logger;
    }

    public function getLogChannel(string $name): LoggerInterface
    {
        // if we haven't yet set up the log system at all, return a NullLogger
        if (!isset($this->logChannels[null]))  {
            return new NullLogger();
        }
        return $this->logChannels[$name] ?? ($this->logChannels[$name] = $this->logChannels[null]->withName($name));
    }

    public function request(): Request
    {
        return $this->activeRequest;
    }

    public function response(): Response
    {
        return $this->activeResponse;
    }

    /**
     * Register a class implementing IComponent as a global component.
     *
     * @param string $className Component class name.
     * @param string|null $name (Optional) Property name to bind component to.
     * @param array|null $config (Optional) Options array to pass to ::configure on instantiation.
     * @return void
     */
    public function addComponent(string $className, ?string $name = null, ?array $config = null): void
    {
        if (!ClassUtil::ImplementsInterface($className, IComponent::class)) {
            throw new TypeError("Component class $className must implement the IComponent interface");
        }
        if (is_null($name)) {
            $name = ClassUtil::GetConst($className, "ComponentName");
            if (is_null($name)) {
                throw new TypeError("Component class $className does not have a default name.");
            }
        }
        if (isset($this->components[$name])) {
            throw new TypeError("Component $name was already defined.");
        }
        $this->components[$name] = [$className, $config ?: []];
    }

    private array $isInComponentCreate = [];

    private function delayedCreateComponent($name, $data): IComponent
    {
        if (isset($this->isInComponentCreate[$name])) {
            throw new TypeError("Component $name causes recursion during delayed construction.");
        }
        try {
            $this->isInComponentCreate[$name] = true;
            [$class, $config] = $data;
            // create the object, and let its constructor trigger dependencies
            $obj = new $class($this);
            if (is_null($obj)) {
                throw new \RuntimeException("Component $name failed to create instance.");
            }
            // then configure it using user-defined values
            $obj->configure($config);
            // store & return
            $this->components[$name] = $obj;
            return $obj;
        } finally {
            $this->isInComponentCreate[$name] = false;
        }
    }

    public function __get(string $name): ?IComponent
    {
        if (isset($this->components[$name])) {
            $data = $this->components[$name];
            if (!is_object($data)) {
                // class was not yet instantiated, do it now
                $data = $this->delayedCreateComponent($name, $data);
            }
            return $data;
        }

        throw new \RuntimeException('Undefined component requested: ' . $name);
    }

    public function run(): void
    {
        // Set up the request globals
        $this->activeRequest = $request = Request::create();
        $this->activeResponse = $response = Response::create();
        // Find a match on the router
        $method = $request->getMethod();
        $path = $request->uri()['path'];
        $path = '/' . ltrim($path, '/');
        /** @var ?RouteMatcher $match */
        if ($match = $this->router->match($method, $path)) {
            $response->setStatus(200);
            try {
                if (!$match->executeRoute($this, $request, $response))
                    return;
            } catch (\Throwable $throwable) {
                $response->setStatus(500);
                $response->setBody(
                    '<html><body><pre>' . (string)$throwable . '</pre></body>'
                );
                // send immediately and then re-throw so other debug code can use it
                Sapi::sendResponse($response);
                throw $throwable;
            }
        } else {
            $response->setStatus(404);
        }
        Sapi::sendResponse($response);
    }

}
