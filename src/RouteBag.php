<?php

namespace Mvarkus;

use Closure;

class RouteBag
{
    /**
     * Holds Route instances in the array
     *
     * @var array
     */
    private $routes = [];

    /**
     * Holds related Route instances which share something
     *
     * @var array
     */
    private $routeGroup = [];
    
    /**
     * Used to determine collection's mode
     *
     * @var boolean
     */
    private $groupMode = false;

    /**
     * Switch group mode to true
     */
    public function enableGroupMode()
    {
        $this->groupMode = true;
    }

    /**
     * Switch group mode to false
     */
    public function disableGroupMode()
    {
        $this->groupMode = false;
    }

    /**
     * Adds Route instance to the array
     *
     * @param Route $route
     */
    private function addRouteToGroup(Route $route)
    {
        $this->routeGroup[] = $route;
    }

    /**
     * Clears route group array
     */
    public function clearRouteGroup()
    {
        $this->routeGroup = [];
    }

    /**
     * Adds route to the collection.
     *
     * Creates route, adds it to grouped routes array if collection is in group mode.
     * Add route to provided methods groups.
     *
     * @param array $methodGroups
     * @param string $rawUriPattern
     * @param Closure|string $resolver
     * @return Route
     */
    public function addRoute(
        array  $methodGroups,
        string $rawUriPattern,
        $resolver
    ): Route {
        $route = Route::makeRoute(
            $rawUriPattern,
            $resolver
        );

        if ($this->groupMode) {
            $this->addRouteToGroup($route);
        }

        foreach($methodGroups as $methodGroup) {
            $this->routes[$methodGroup][] = $route;
        }

        return $route;
    }

    /**
     * Returns routes which belong to the given method
     *
     * @param string $method
     * @return array
     */
    public function getRoutesByMethod(string $method): array
    {
        return $this->routes[$method] ?? [];
    }

    /**
     * Adds prefix to each grouped route
     *
     * @param string $prefix
     * @return RouteBag
     */
    public function prefix(string $prefix)
    {
        foreach ($this->routeGroup as $route) {
            $route->setUriPrefix($prefix);
        }

        return $this;
    }

    /**
     * Adds namespace suffix to each grouped route
     *
     * @param string $namespace
     *
     * @return RouteBag
     */
    public function namespace(string $namespace)
    {
        foreach ($this->routeGroup as $route) {
            $route->setNamespacePrefix($namespace);
        }
        return $this;
    }

    /**
     * Adds regular expresion replacements to each grouped route
     *
     * @param array $regExpReplacements
     *
     * @return RouteBag
     */
    public function with(array $regExpReplacements)
    {
        foreach ($this->routeGroup as $route) {
            $route->with($regExpReplacements);
        }
        return $this;
    }

    /**
     * Adds default parameters to each grouped route
     *
     * @param array $parameters
     *
     * @return RouteBag
     */
    public function default(array $parameters)
    {
        foreach ($this->routeGroup as $route) {
            $route->default($parameters);
        }
        return $this;
    }
}
