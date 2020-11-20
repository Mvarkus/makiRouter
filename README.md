# MakiRouter - Php routing system

PHP router which navigates request through an application.

## Table of contents
* [Technologies](#technologies)
* [Available functions](#available-functions)
* [Rules to follow and just nice to know](#rules-to-follow-and-just-nice-to-know)
    * [URI parameters](#uri-parameters)
    * [Regular expression replacements](#regular-expression-replacements)
    * [Optional parameters](#optional-parameters)
* [Usage Examples](#usage-examples)
## Technologies
* Php >=5.6
* Symfony http foundation ^5.1
## Available functions

```php
MakiRouter::get(string $uri, Closure|string $resolver);

MakiRouter::post(string $uri, Closure|string $resolver);

MakiRouter::patch(string $uri, Closure|string $resolver);

MakiRouter::put(string $uri, Closure|string $resolver);

MakiRouter::delete(string $uri, Closure|string $resolver);

MakiRouter::any(string $uri, Closure|string $resolver);

MakiRouter::match(array $methods, string $uri, Closure|string $resolver);

MakiRouter::group(array $settings, Closure $callback);

```
## Rules to follow and just nice to know

### URI parameters

All parameters must have unique names in one route or grouped routes. 
It is okay to use same names in separate routes.
   
#### Examples

##### Good

File: **routes/web.php**
```php
use Mvarkus\MakiRouter as Router;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

Router::get(
    '/users/{firstname}/{secondname}', // <--- Good
    function (
        string $firstName, //
        string $secondName
    ) {
        return new Response("Full name: $firstName $secondName");
    }
)->with([
    'firstname|secondname' => '[a-zA-Z]+'
]);
// Visiting /users/Maksim/Varkus will give us: Full name: Maksim Varkus
```

##### Bad

File: **routes/web.php**
```php
use Mvarkus\MakiRouter as Router;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

Router::get(
    '/calc/{digit}/{digit}', // <--- Bad
    function (
        int $digitOne, //
        int $digitTwo
    ) {
        return new Response("{$digitOne} + {$digitTwo} =" . $digitOne+$digitTwo);
    }
)->with([
    'digit' => '[0-9]'
]);
// Visiting /calc/1/1 will give us: Warning: preg_match(): Compilation failed: two named subpatterns have the same name.
```

### Regular expression replacements

If regular expression replacements have not been defined globaly in Router class,
you must define it using "with" method on single route and
"with" setting when defining a group.

#### Examples

##### Good

File: **routes/web.php**
```php
use Mvarkus\MakiRouter as Router;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

Router::get(
    '/users/{id}',
    function (
        int $userId
    ) {
        return new Response("User #{$id}");
    }
)->with([ // <--- Good
    'id' => '[0-9]'
]);
// Visiting /users/1 will give us: User #1
```

##### Bad

File: **routes/web.php**
```php
use Mvarkus\MakiRouter as Router;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

Router::get(
    '/users/{id}',
    function (
        int $userId
    ) {
        return new Response("User #{$id}");
    }
); // <-- Bad, no "with" method specifying the parameter
// Visiting /users/1 will give us: HTTP ERROR 404
```
### Optional parameters

If you have optional parameters, you can set default
parameter values using "default" method on the single route
and "default" setting when defining a group.
If nothing was set, null will be default parameter value.

#### Examples

##### With default method

File: **routes/web.php**
```php
use Mvarkus\MakiRouter as Router;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

Router::get(
    '/dashboard/{id?}',
    function (
        $dashboardId // Default is 3
    ) {
        return new Response("Dashboard #{$dashboardId}");
    }
)->with([
    'id' => '[0-9]'
])->default([ // <--- Defining default parameter
    'id' => 3
]);
// Visiting /users/1 will give us: Dashboard #1
// Visiting /users will give us: Dashboard #3
```

##### Without default method

File: **routes/web.php**
```php
use Mvarkus\MakiRouter as Router;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

Router::get(
    '/dashboard/{id?}',
    function (
        $dashboardId // Default is null
    ) {
        return new Response("Dashboard #{$dashboardId}");
    }
)->with([
    'id' => '[0-9]'
]); // <--- No default
// Visiting /users/1 will give us: Dashboard #1
// Visiting /users will give us: Dashboard #
```

## Usage Examples

### Simple GET route Nr.1

File: **routes/web.php**
```php
use Mvarkus\MakiRouter as Router;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

// Simple get request
Router::get('/', function () {
    return new Response('Homepage');
});
```

### Simple GET route Nr.2

File: **routes/web.php**
```php
use Mvarkus\MakiRouter as Router;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

// Simple get request
Router::get('/login', function () {
    return new Response('Show login form');
});
```

### Predefine common pattern replacements

Usually you will use patterns like: id, name, token, pid, uid etc.

Instead of defining these in each route, you can predefine them in getSharedPatterns method,
which is located in Mvarkus\MakiRouter class.

The method simply returns array of patterns, 
the array will be used by every route to check for replacement patterns.

File: **src/MakiRouter.php**
Line: **31**
```php
/**
 * Returns shared patterns.
 *
 * @return array
 */
public static function getSharedPatterns(): array
{
    return [
        'id|category_id|user|product|post' => '[0-9]+',
        'firstname|middlename|name|surname|secondname|title' => '[a-zA-Z]+'
    ];
}
```

### PUT route

File: **routes/web.php**
```php
use Mvarkus\MakiRouter as Router;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

// Put request
// id has been defined globaly in Mvarkus\MakiRouter class, line: 31
Router::put('/users/{id}', function (
    int $id,
    Request $request
) {
    return new Response("Update user #{$id}. New details: " . json_encode($request->request->all()));
});
```

### PATCH route

File: **routes/web.php**
```php
use Mvarkus\MakiRouter as Router;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

// Patch request
// id has been defined globaly in Mvarkus\MakiRouter class, line: 31
Router::put('/users/{id}', function (
    int $id,
    Request $request
) {
    return new Response("Update user #{$id}. New details: " . json_encode($request->request->all()));
});
```

### DELETE route

File: **routes/web.php**
```php
use Mvarkus\MakiRouter as Router;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

// Delete request
// id has been defined globaly in Mvarkus\MakiRouter class, line: 31
Router::delete('/posts/{id}', function (int $id) {
    return new Response("Remove post with id #{$id}");
});
```

### More than one parameter route

File: **routes/web.php**
```php
use Mvarkus\MakiRouter as Router;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

// More than one parameter
// firstname and second name parameters have been defined globaly in Mvarkus\MakiRouter class, line: 31
// So you do not have to define replacements here.
Router::get(
    '/users/{firstname}/{secondname}',
    function (
        string $firstName,
        string $secondName
    ) {
        return new Response("Full name: $firstName $secondName");
    }
);
```

### Route with optional parameter 

File: **routes/web.php**
```php
use Mvarkus\MakiRouter as Router;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

// Get method with optional parameter
// id has been defined globaly in Mvarkus\MakiRouter class, line: 31
// So you do not have to define replacements here.
Router::get(
    '/users/{id?}/username',
    function (
        $id // If nothing passed in query, null will be given
    ) {
        if ($id === null) {
            return new Response("Username of logged user");
        }

        return new Response("Usedname of an user with id={$id}");;
    }
);
```

### Group of routes

```php
Available settings for group: [
    'prefix' => 'somerpefix/{id?}',
    'with'   => [
        'id' => '[0-9]+'
    ],
    'default' => [
        'id' => 23
    ],
    'namespace' => 'Admin'
]
```

File: **routes/web.php**
```php
use Mvarkus\MakiRouter as Router;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

// Register group of routes which share settings
Router::group([
    'prefix' => 'dashboard/{dashboardID?}',
    'with' => [
        'dashboardID' => '[0-9]+'
    ],
    'default' => [
        'dashboardID' => 4 // If you hit /dashboard/posts/2. Default dashboard will be used
    ]
],
function() {

    // Example uri: /dashboard/3
    Router::get('/', function (int $dashboardId) {

        return new Response("Dasboard #{$dashboardId}");

    });

    // Example uri: /dashboard/1/posts/4
    Router::get('posts/{pid}', function (
        int $dashboardId,
        int $postId
    ) {

        return new Response("Post id: {$postId} on dashboard #{$dashboardId}");
    
    })->with(
        ['pid' => '[0-9]+']
    );

});
```

### Routes with Controllers

File: **routes/web.php**
```php
use Mvarkus\MakiRouter as Router;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

// Route with controllers
Router::get('/register/{formId?}', 'User\RegisterController@index')
    ->with(['formId' => '[0-9]+'])
    ->default(['formId' => 2]);

Router::post('/register', 'User\RegisterController@register');
```

### Grouped controller routes with namespace and URI preix

File: **routes/web.php**
```php
use Mvarkus\MakiRouter as Router;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

Router::group([
    'namespace' => 'Admin',
    'prefix' => 'panel_22_3_admin'
], function () {

    // Example uri: panel_22_3_admin/posts/2
    Router::get('posts/{id}', 'PostController@show'); // Final controller's name: Admin\PostController@show

    // Example uri: panel_22_3_admin/posts
    Router::post('/posts', 'PostController@store'); // Final controller's name: Admin\PostController@store

});
```
### Match method Route

File: **routes/web.php**
```php
use Mvarkus\MakiRouter as Router;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

// Match method route
Router::match(['put', 'patch'], '/match/users/{id}', function (
    int $id,
    Request $request
) {
    return new Response("Update user #{$id}");
});
```

### Any method Route

File: **routes/web.php**
```php
use Mvarkus\MakiRouter as Router;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

// Any method route
Router::any('/any', function () {
    return new Response("Any method route");
});
```
