<?php

namespace MakiGon\Controllers\Admin;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class PostController
{
    public function show(int $postId)
    {
        return new Response("Show post control page #{$postId}");
    }

    public function store(Request $request)
    {
        return new Response("Store post. Details: " . json_encode($request->request->all()));
    }

}