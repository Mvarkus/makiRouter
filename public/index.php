<?php

require __DIR__ . "/../vendor/autoload.php";
require "../src/helpers.php";

MakiGon\MakiRouter::init('../routes/web.php');

MakiGon\MakiRouter::routeRequest(
    Symfony\Component\HttpFoundation\Request::createFromGlobals()
)->send();
