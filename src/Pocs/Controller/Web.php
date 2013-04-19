<?php

namespace Pocs\Controller;

use Pocs\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

class Web extends Controller
{
    /**
     * Init Web controller.
     */
    public function initialize()
    {
        $this->app->before(array($this, 'before'));
        $this->app->get('/', array($this, 'index'))->bind('homepage');
        $this->app->get('/login', array($this, 'login'))->bind('login');
    }

    /**
     * Before (checking if the app is installed).
     */
    public function before(Request $request) {
        $token = $this->app['security']->getToken();
        if (!strpos($request->getRequestUri(), '/login')
            && ($token->getUser() === null || $token->getUser() == 'anon.')) {
            $form = $this->app['form.factory']->createBuilder('form')
                ->add('username', 'text', array('label' => 'Username',
                      'data' => $this->app['session']->get('_security.last_username'))
                )
                ->add('password', 'password', array('label' => 'Password'))
                ->getForm();

            return $this->app->redirect($this->app['url_generator']
                ->generate('login'));
        }
    }

    /**
     * Index page
     */
    public function index() {
        $this->app['session']->getFlashBag()->add('warning', 'Warning flash message');
        $this->app['session']->getFlashBag()->add('info', 'Info flash message');
        $this->app['session']->getFlashBag()->add('success', 'Success flash message');
        $this->app['session']->getFlashBag()->add('error', 'Error flash message');

        return $this->app['twig']->render('index.html.twig');
    }

    /**
     * Login page
     */
    public function login(Request $request) {
        $form = $this->app['form.factory']->createBuilder('form')
            ->add('username', 'text',
                array('label' => 'Username',
                      'data' => $this->app['session']->get('_security.last_username'))
            )
            ->add('password', 'password', array('label' => 'Password'))
            ->getForm();

        return $this->app['twig']->render('login.html.twig', array(
            'form'  => $form->createView(),
            'error' => $this->app['security.last_error']($request),
        ));
    }
}

