<?php

namespace Pocs\Controller;

use Pocs\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

class Api extends Controller
{
    public function initialize()
    {
        $this->app->get('/api/comments', array($this, 'list'));
        $this->app->post('/api/comments', array($this, 'add'));
    }
}

