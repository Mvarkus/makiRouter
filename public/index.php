<?php

require __DIR__ . "/../vendor/autoload.php";
require "../src/helpers.php";

Mvarkus\MakiRouter::init('../routes/web.php');

Mvarkus\MakiRouter::routeRequest(
    Symfony\Component\HttpFoundation\Request::createFromGlobals()
)->send();
