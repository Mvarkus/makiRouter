<?php

namespace Mvarkus\Controllers\User;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class RegisterController
{
    public function index($formId)
    {
        return new Response("Show register form #{$formId}");
    }

    public function register(Request $request)
    {
        return new Response("Register user. Details: " . json_encode($request->request->all()));
    }
}