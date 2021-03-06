<?php

namespace Mvarkus;

use Closure;
use Exception;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * This class wraps up Router's usage.
 *
 * It holds router instance and shared data which can be used in
 * any part of the code. All functionality is held in router instance,
 * the class just wraps up its usage. This has been done to make it
 * global and more convenient to use.
 *
 */
class MakiRouter
{
    /**
     * Router instance
     *
     * @var Router|null
     */
    private static $router = null;

    /**
     * Controllers location
     *
     * @var string
     */
    public static $controllersNamespace;

    /**
     * Holds global replacements patterns
     *
     * @var array
     */
    public static $sharedPatterns;

    /**
     * Initiates router settings
     *
     * @param string $routesFile
     * @param string $controllersNamespace
     * @param array $sharedPatterns
     * @throws Exception
     */
    public static function init(
        string $routesFile,
        string $controllersNamespace = '',
        array $sharedPatterns = []
    ) {
        static::$controllersNamespace = $controllersNamespace;
        static::$sharedPatterns = $sharedPatterns;

        if (!static::registerRoutes($routesFile)) {
            throw new Exception("File {$routesFile} does not exist");
        }
    }

    /**
     * Tries to register routes
     *
     * @param string $routesFile - file which hold all routes
     * @return bool - registration result
     */
    protected static function registerRoutes(string $routesFile): bool
    {
        if (file_exists($routesFile)) {
            require_once $routesFile;
            return true;
        }

        return false;
    }

    /**
     * Returns Router's instance.
     *
     * If the instance does not exist, creates one.
     *
     * @return Router
     */
    private static function router(): Router
    {
        if (static::$router === null) {
            static::$router = new Router(
                ['get', 'post', 'put', 'delete', 'patch'],
                new RouteBag()
            );
        }

        return static::$router;
    }

    /**
     * Registers GET method route
     *
     * @param string $rawUriPattern
     * @param Closure|string $resolver
     *
     *
     * @return Route - returns created route
     */
    public static function get(
        string $rawUriPattern,
        $resolver
    ): Route {
        return self::router()->combine(
            ['get'],
            $rawUriPattern,
            $resolver
        );
    }

    /**
     * Registers POST method route
     *
     * @param string $rawUriPattern
     * @param Closure|string $resolver
     *
     * @return Route - returns created route
     */
    public static function post(
        string $rawUriPattern,
        $resolver
    ): Route {
        return self::router()->combine(
            ['post'],
            $rawUriPattern,
            $resolver
        );
    }

    /**
     * Registers PUT method route
     *
     * @param string $rawUriPattern
     * @param Closure|string $resolver
     *
     * @return Route - returns created route
     */
    public static function put(
        string $rawUriPattern,
        $resolver
    ): Route {
        return self::router()->combine(
            ['put'],
            $rawUriPattern,
            $resolver
        );
    }

    /**
     * Registers DELETE method route
     *
     * @param string $rawUriPattern
     * @param Closure|string $resolver
     *
     * @return Route - returns created route
     */
    public static function delete(
        string $rawUriPattern,
        $resolver
    ): Route {
        return self::router()->combine(
            ['delete'],
            $rawUriPattern,
            $resolver
        );
    }

    /**
     * Registers PATCH method route
     *
     * @param string $rawUriPattern
     * @param Closure|string $resolver
     *
     * @return Route - returns created route
     */
    public static function patch(
        string $rawUriPattern,
        $resolver
    ): Route {
        return self::router()->combine(
            ['patch'],
            $rawUriPattern,
            $resolver
        );
    }

    /**
     * Registers provided methods routes
     *
     * @param array $methods - e.g. ['post', 'get']
     * @param string $rawUriPattern
     * @param Closure|string $resolver
     *
     * @return Route - returns created route
     */
    public static function match(
        array $methods,
        string $rawUriPattern,
        $resolver
    ): Route {
        return self::router()->combine(
            $methods,
            $rawUriPattern,
            $resolver
        );
    }

    /**
     * Registers route to all supported methods
     *
     * @param string $rawUriPattern
     * @param Closure|string $resolver
     *
     * @return Route - returns created route
     */
    public static function any(
        string $rawUriPattern,
        $resolver
    ): Route {
        return self::router()->combine(
            self::router()->getAllowedMethods(),
            $rawUriPattern,
            $resolver
        );
    }

    /**
     * Routes request.
     *
     * Using provided URI and METHOD finds needed route.
     *
     * @param Request $request
     *
     * @return Response
     */
    public static function routeRequest(Request $request): Response
    {
        return static::router()->setRequest($request)->routeRequest(
            $request->getRealMethod(),
            $request->getPathInfo()
        );
    }

    /**
     * Groups routes
     *
     * The method groups routes which share same settings like:
     * prefix, namespace, default values.
     *
     * @param array $settings
     * @param Closure $callback
     * @return RouteBag
     */
    public static function group(
        array $settings,
        Closure $callback
    ): RouteBag {
        return static::router()->group($settings, $callback);
    }
}
