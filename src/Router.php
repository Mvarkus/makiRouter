<?php

namespace Mvarkus;

use Closure;
use Exception;
use Mvarkus\Helpers\TrimSlashes;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Routes request through an app
 */
class Router
{

    use TrimSlashes;

    /**
     * Request instance
     * 
     * @var Request
     */
    protected $request;

    /**
     * Holds routes
     *
     * @var RouteBag
     */
    protected $routeCollection;

    /**
     * Holds supported methods
     *
     * @var array
     */
    protected $allowedMethods = [];

    /**
     * Defines class properties
     *
     * @param array $allowedMethods
     * @param RouteBag $routeBag
     */
    public function __construct(
        array $allowedMethods,
        RouteBag $routeBag
    ) {
        foreach($allowedMethods as $allowedMethod) {
            $this->allowedMethods[] = strtolower($allowedMethod);
        }
        
        $this->routeCollection = $routeBag;
    }

    /**
     * Sets request property
     *
     * @param Request $request
     * @return Router
     */
    public function setRequest(Request $request)
    {
        $this->request = $request;
        return $this;
    }

    /**
     * Registers route.
     *
     * Combines provided data in order to generate new route.
     *
     * @param array $methods
     * @param string $rawUriPattern
     * @param Closure|string $resolver
     *
     * @return Route
     */
    public function combine(
        array  $methods,
        string $rawUriPattern,
        $resolver
    ): Route {

        if (!$this->requestMethodIsSupported($methods)) {
            throw new Exception("Failed registering route: method is not supported.", 1);
        }

        return $this->routeCollection->addRoute(
            $methods,
            $rawUriPattern,
            $resolver
        );

    }

    /**
     * Return allowed request methods
     *
     * @return array
     */
    public function getAllowedMethods(): array
    {
        return $this->allowedMethods;
    }

    /**
     * Checks whether provided request method is supported by the router.
     *
     * @param array $methods
     * @return boolean
     */
    private function requestMethodIsSupported(array $methods): bool
    {
        foreach($methods as $method) {
            if (!in_array($method, $this->allowedMethods)) {
                return false;
            } 
        }

        return true;
    }

    /**
     * Groups routes.
     *
     * Enables group mode in collection instance to register routes as a group.
     * After having a group, applies given settings to the group.
     *
     * @param array $settings
     * @param Closure $callback
     * @return RouteBag
     */
    public function group(
        array $settings,
        Closure $callback
    ): RouteBag {
        $this->routeCollection->clearRouteGroup();
        $this->routeCollection->enableGroupMode();
        call_user_func($callback);

        foreach ($settings as $methodName => $settingParameters) {
            if (method_exists($this->routeCollection, $methodName)) {
                call_user_func_array(
                    [$this->routeCollection, $methodName],
                    [$settingParameters]
                );
            }
        }

        $this->routeCollection->disableGroupMode();
        return $this->routeCollection;
    }

    /**
     * Routes request.
     *
     * First, it checks whether the requested method is supported by router.
     * Second, it removes unnecessary slashes from the start and end.
     * Third, it takes all routes by given method and tries to find requested one.
     * If nothing was found, sets response status to 404. If route was found, call its
     * resolver and return the response.
     *
     * @param string $requestMethod
     * @param string $requestUri
     * @return Response
     */
    public function routeRequest(
        string $requestMethod,
        string $requestUri
    ): Response {

        $requestMethod = strtolower($requestMethod);

        if (!$this->requestMethodIsSupported([$requestMethod])) {
            return new Response(null, Response::HTTP_METHOD_NOT_ALLOWED);
        }

        $requestUri = $this->trimSlashesFromTheEnd($requestUri);
        $requestUri = $this->trimExtraSlashesFromTheStart($requestUri);

        foreach($this->routeCollection->getRoutesByMethod($requestMethod) as $route) {

            if ($route->matchRequestUri($requestUri) !== false) {
                return $route->resolve($this->request);
            }

        }

        return new Response(null, Response::HTTP_NOT_FOUND);
    }
}
