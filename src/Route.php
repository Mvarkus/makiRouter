<?php

namespace Mvarkus;

use Closure;
use Mvarkus\Helpers\TrimSlashes;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class Route
{

    use TrimSlashes;

    /**
     * Request method to which the route is assigned
     *
     * @var string
     */
    protected $method;

    /**
     * Pattern to match requested uri
     *
     * @var string
     */
    protected $uriPattern;

    /**
     * Route`s resolver.
     * Can be callback or string which holds controller and action name
     *
     * @var Closure|string
     */
    protected $resolver;

    /**
     * Holds route`s URI prefix
     *
     * @var string|null
     */
    protected $uriPrefix;

    /**
     * Namespace prefix will be used on controller creation
     *
     * @var string|null
     */
    protected $namespacePrefix;

    /**
     * Holds namespace of controllers
     *
     * @var string
     */
    protected $controllerNamespace;

    /**
     * Regular replacements will be used when
     * replacing raw uri pattern to regular expresion pattern.
     *
     * E.g. /user/{id} => /user/([0-9]+)
     *
     * @var array
     */
    protected $regExpReplacements = [];

    /**
     * Holds matched route parameters
     *
     * @var array
     */
    protected $routeParameters = [];

    /**
     * If route has optional(user/{id?}) parameters,
     * these default values will be used if nothing was entered.
     *
     * @var array
     */
    protected $routeDefaultParameters = [];

    /**
     * Sets route pattern, resolver and regular expression replacements
     *
     * @param string $rawUriPattern
     * @param Closure|string $resolver
     */
    public function __construct(
        string $rawUriPattern,
        $resolver
    ) {
        $this->uriPattern = $rawUriPattern;
        $this->resolver   = $resolver;
        $this->method     = strtolower($_SERVER['REQUEST_METHOD']);

        $this->controllerNamespace = MakiRouter::$controllersNamespace;
        $this->with(MakiRouter::$sharedPatterns);
    }

    /**
     * Builds parameters core.
     *
     * In order to give parameters to a resolver in right order,
     * this function builds right order using uri pattern.
     * If route does not have default parameter, null will be used.
     */
    protected function defineDefaultParameters()
    {
        $result = preg_match_all('~{([0-9a-zA-Z]+)\??}~', $this->uriPattern, $matches);
        // If route has no parameters, skip this action
        if ($result == 0) return;
        $parameterKeys = array_slice($matches, 1)[0];

        foreach ($parameterKeys as $key) {
            $this->routeParameters[$key] = $this->routeDefaultParameters[$key] ?? null;
        }
    }

    /**
     * Prepares route for matching with requested uri.
     *
     * The method scrubs uri from extra slashes.
     * Defines default parameters.
     * Then if the route has any regular expression replacements, replaces them.
     */
    protected function prepareForMatching()
    {
        $this->uriPattern = $this->scrubUriPattern($this->uriPattern, $this->uriPrefix);
        $this->defineDefaultParameters();

        // Transforms string from /users/{something?} to /users{something?}.
        // The final pattern will look like this ~^/users(?P<something>/[0-9]+)?$~
        $this->uriPattern = preg_replace('~/({[0-9a-zA-Z]+\?})~', '$1', $this->uriPattern);

        if (!empty($this->regExpReplacements)) {
            $this->uriPattern = $this->replaceHoldersWithRegExp(
                $this->regExpReplacements,
                $this->uriPattern
            );
        }

        $this->uriPattern = '~^'.$this->uriPattern.'$~';
    }

    /**
     * Adds regular expression replacements
     *
     * @param array $regExpReplacements
     * @return Route
     */
    public function with(array $regExpReplacements)
    {
        $this->regExpReplacements = $regExpReplacements+$this->regExpReplacements;
        return $this;
    }

    /**
     * Adds default parameters for the route
     *
     * @param array $parameters
     * @return Route
     */
    public function default(array $parameters)
    {
        foreach ($parameters as $parameterKeys => $defaultValue) {
            foreach (explode('|', $parameterKeys) as $parameterKey) {
                $this->routeDefaultParameters[$parameterKey] = $defaultValue;
            }
        }

        return $this;
    }

    /**
     * Sets route's prefix
     *
     * @param string $uriPrefix
     * @return Route
     */
    public function setUriPrefix(string $uriPrefix)
    {
        $this->uriPrefix = $uriPrefix;
        return $this;
    }

    /**
     * Sets namespace prefix
     *
     * @param string $prefix
     * @return Route
     */
    public function setNamespacePrefix(string $prefix)
    {
        $this->namespacePrefix = $prefix;
        return $this;
    }

    /**
     * Returns namespace prefix
     *
     * @return string
     */
    public function getNamespacePrefix()
    {
        return $this->namespacePrefix;
    }

    /**
     * Removes slashes and adds prefix.
     *
     * Cleans string pattern from slashes in then end and start.
     * If prefix exists it is added to the pattern.
     *
     * @param string $uriPattern
     * @param string|null $uriPrefix
     * @return string
     */
    protected function scrubUriPattern(
        string $uriPattern,
        string $uriPrefix = null
    ): string {
        $uriPattern = $this->trimSlashesFromTheEnd($uriPattern);
        $uriPattern = $this->trimExtraSlashesFromTheStart($uriPattern);

        if ($uriPrefix !== null) {
            $uriPrefix = $this->trimExtraSlashesFromTheStart($uriPrefix);
            $uriPrefix = $this->trimSlashesFromTheEnd($uriPrefix);

            // In case if prefix is for example: /admin and actual pattern is /
            // We trim it again to get rid of that extra slash to have /admin pattern
            return $this->trimSlashesFromTheEnd($uriPrefix.$uriPattern);
        }
        return $uriPattern;
    }

    /**
     * Matches request uri.
     *
     * Prepares route for matching.
     * Tries to match route pattern with requested uri.
     * If it was matched successfully, add route parameters.
     *
     * @param string $requestUri
     * @return boolean
     */
    public function matchRequestUri(string $requestUri): bool
    {
        $this->prepareForMatching();

        $matchResult = preg_match(
            $this->uriPattern,
            $requestUri,
            $matches
        );

        if ($matchResult !== 0 && $matches !== null) {
            $parameters = array_slice($matches, 1);

            foreach ($parameters as $key => $parameter) {
                // Get only string keys
                if (!is_int($key) && $parameters[$key] !== '') {
                    $this->routeParameters[$key] = $parameter;
                }
            }

            return true;
        }

        return false;
    }

    /**
     * Calls route's resolver.
     *
     * The method tries to find out whether the resolver is Closure or controller.
     * Then it calls it with route's parameters.
     *
     * @param Request $request
     * @return Response
     */
    public function resolve(Request $request): Response
    {
        $this->routeParameters['request'] = $request;
        $parameters = [];

        if (is_callable($this->resolver)) {
            $reflection = new \ReflectionFunction($this->resolver);

            foreach ($reflection->getParameters() as $parameter)
                if (array_key_exists($parameter->name, $this->routeParameters))
                    $parameters[$parameter->name] = $this->routeParameters[$parameter->name];

            return call_user_func_array($this->resolver, $parameters);
        }

        list($controller, $action) = explode('@', $this->resolver);
        $controller = $this->getNamespacePrefix() === null ?
            "{$this->controllerNamespace}\\{$controller}" :
            "{$this->controllerNamespace}\\{$this->namespacePrefix}\\{$controller}";

        $reflection = new \ReflectionMethod($controller, $action);

        foreach ($reflection->getParameters() as $parameter)
            if (array_key_exists($parameter->name, $this->routeParameters))
                $parameters[] = $this->routeParameters[$parameter->name];

        return call_user_func_array([
            new $controller,
            $action
        ],
            $parameters
        );
    }

    /**
     * Replaces raw uri pattern with regular expression pattern.
     *
     * E.g. /user/{id?} -> /user/?([0-9]+)?
     *
     * @param array  $userReplacements
     * @param string $uriPattern
     * @return string
     */
    protected function replaceHoldersWithRegExp(
        array  $userReplacements,
        string $uriPattern
    ): string {
        // Create empty arrays
        $patterns = $replacements = [];

        foreach ($userReplacements as $patternSet => $replacement) {
            foreach (explode('|', $patternSet) as $pattern) {

                if (strpos($uriPattern, "{".$pattern."?}")) {
                    $patterns[]     = "~{".$pattern."\?}~";
                    $replacements[] = "(/(?P<$pattern>$replacement))?";
                } else {
                    $patterns[]     = "~{".$pattern."}~";
                    $replacements[] = "(?P<$pattern>$replacement)";
                }

            }
        }

        return preg_replace($patterns, $replacements, $uriPattern);
    }

    /**
     * Created Route instance and returns it
     *
     * @param string $rawUriPattern
     * @param Closure|string $resolver
     *
     * @return Route
     */
    public static function makeRoute(
        string $rawUriPattern,
        $resolver
    ): Route {
        return new Route(
            $rawUriPattern,
            $resolver
        );
    }
}
