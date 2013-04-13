<?php

namespace Pocs\Controller;

use Pocs\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

class Web extends Controller
{
    public function initialize()
    {
        $this->app->get('/', array($this, 'index'))->bind('homepage');
    }

    public function index() {
      $this->app['session']->getFlashBag()->add('warning', 'Warning flash message');
      $this->app['session']->getFlashBag()->add('info', 'Info flash message');
      $this->app['session']->getFlashBag()->add('success', 'Success flash message');
      $this->app['session']->getFlashBag()->add('error', 'Error flash message');

      return $this->app['twig']->render('index.html.twig');
    }
}

