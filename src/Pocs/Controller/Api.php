<?php

namespace Pocs\Controller;

use Pocs\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;

class Api extends Controller
{
    /**
     * Init API controller.
     */
    public function initialize()
    {
        $this->app->before(array($this, 'before'));
        $this->app->post('/api/comments', array($this, 'add'));
        $this->app->get('/api/comments', array($this, 'get'));
        $this->app->match('/api/comments', array($this, 'options'))
          ->method('OPTIONS');
    }

    /**
     * Make sure to prepare the json when recieving one.
     */
    public function before(Request $request) {
        if (0 === strpos($request->headers->get('Content-Type'), 'application/json')) {
            $data = json_decode($request->getContent(), true);
            $request->request->replace(is_array($data) ? $data : array());
        }
    }

    /**
     * Handle the POST Request.
     */
    public function add(Request $request) {
      $callback = $request->request->get('callback');
      $response = new JsonResponse();
      $response->setCallback($callback);
      $response->setData(array('status' =>'success'));
      return $response;
    }

    /**
     * Handle the GET request.
     */
    public function get(Request $request) {
        $return = array(
          'status' => 'success',
          'title' => 'test',
          'comments' => array(
            array('id' => 1, 'comment'=> 'test1', 'date' => '2013-04-10 10:15:30', 'user' => 'bacardi55'),
            array('id' => 2, 'comment'=> 'test2', 'date' => '2013-04-11 10:15:30', 'user' => 'user2'),
          ),
        );

        $callback = $request->query->get('callback');
        $response = new JsonResponse();
        $response->setCallback($callback);
        $response->setData($return);
        return $response;
    }

    /**
     * Handle the OPTIONS request sent when doiing CORS request.
     */
    public function options(Request $request) {
        $response = new Response();
        $response->headers->set('Access-Control-Allow-Origin', '*');
        $response->headers->set('Allow', 'POST, GET, OPTIONS');
        $response->headers->set('Access-Control-Allow-Headers', 'origin, x-csrftoken, content-type, accept');
        return $response;
    }
}

