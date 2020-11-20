<?php

use Mvarkus\MakiRouter as Router;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

// Simple get request
Router::get('/', function () {
    return new Response('Homepage');
});

// Simple get request
Router::get('/login', function () {
    return new Response('Show login form');
});

// Put request
Router::put('/users/{id}', function (
    int $id, Request $request
) {
    return new Response("Update user #{$id}. New details: " . json_encode($request->request->all()));
});

// Patch request
Router::patch('/users/{id}', function (
    int $id, Request $request
) {
    return new Response("Update user #{$id}. New details: " . json_encode($request->request->all()));
});

// Delete request
Router::delete('/posts/{id}', function (int $id) {
    return new Response("Remove post with id #{$id}");
});

// More than one parameter
// firstname and second name parameters have been defined globally in MakiRouter class, line: 35
// So you do not have to define replacements here.
Router::get(
    '/users/{firstname}/{lastname}',
    function (
        string $firstName, string $lastName
    ) {
        return new Response("Full name: $firstName $lastName");
    }
);

// Get method with optional parameter
Router::get(
    '/users/{id?}/username',
    function (
        $id // If nothing passed in query, null will be given
    ) {
        if ($id === null) return new Response("Username of logged user");

        return new Response("Username of an user with id={$id}");
    }
);

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
            return new Response("Dashboard #{$dashboardId}");
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
    }
);

// Route with controller
Router::get('/register/{formId?}', 'User\RegisterController@index')
    ->with(['formId' => '[0-9]+'])
    ->default(['formId' => 2]);

Router::post('/register', 'User\RegisterController@register');

Router::group([
    'namespace' => 'Admin',
    'prefix' => 'panel_22_3_admin'
], function () {
    // Example uri: panel_22_3_admin/posts/2
    Router::get('posts/{id}', 'PostController@show');

    // Example uri: panel_22_3_admin/posts
    Router::post('/posts', 'PostController@store');
});

// Match method route
Router::match(['put', 'patch'], '/match/users/{id}', function (
    int $id, Request $request
) {
    return new Response("Update user #{$id}");
});

// Any method route
Router::any('/any', function () {
    return new Response("Any method route");
});