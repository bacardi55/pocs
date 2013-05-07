<?php

namespace Pocs\Controller;

use Pocs\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;

use Pocs\Entity\Frontend;

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
     *
     * @param Symfony\Component\HttpFoundation\Request
     *   The request.
     *
     * @return Symfony\Component\HttpFoundation\JsonResponse
     *   A Jsonresponse.
     */
    public function add(Request $request)
    {
        if (!$this->isValidCommentRequest($request)) {
             return $this->returnErrorResponse("Invalid arguments.");
        }
        $frontend = $this->getFrontendByApikey($request->get('key'));
        if (!$frontend) {
            return $this->returnErrorResponse('The Api Key isn\'t valid');
        }
        $url = $request->get('href');
        $position = strpos($url, $frontend['base_url']);
        $path = substr($url, $position + strlen($frontend['base_url']));
        if (FALSE === $position) {
            return $this->returnErrorResponse(
                'The Api Key doesn\'t match the referer url'
            );
        }
        if (preg_match('#\#/$#', $path)) {
            $path = substr($path, 0, -2);
        }
        if (!$path) {
            $path = '/';
        }

        $stmt = $this->app['db']->executeQuery(
            'SELECT id FROM urls WHERE frontend_id = ? AND url = ?',
            array($frontend['id'], $path)
        );

        if (!$url = $stmt->fetch()) {
            $this->app['db']->insert('urls', array(
                'frontend_id' => $frontend['id'],
                'url' => $path,
            ));
            $urlId = $this->app['db']->lastInsertId();
        } else {
            $urlId = $url['id'];
        }

        $comment = $request->get('newComment');
        $this->app['db']->insert('comments', array(
            'comment' => $comment['com'],
            'user_name' => $comment['name'],
            'user_email' => $comment['email'],
            'url_id' => $urlId,
            'date'=> date('c'),
        ));

        $commentId = $this->app['db']->lastInsertId();

        $response = new JsonResponse();
        $response->headers->set('Access-Control-Allow-Origin', '*');
        $response->setData(array(
            'status' => 'success', 'message' => 'Comment added successfully')
        );
        return $response;
    }

    /**
     * Handle the GET request.
     */
    public function get(Request $request) {
        $frontend = $this->getFrontendByApikey($request->query->get('key'));
        if (!$frontend) {
            return $this->returnErrorResponse('The Api Key isn\'t valid',
                $request->query->get('callback')
            );
        }
        if (!isset($_SERVER['HTTP_REFERER'])) {
            return $this->returnErrorResponse('The referer doesn\'t match',
                $request->query->get('callback')
            );
        }
        $url = $_SERVER['HTTP_REFERER'];
        $position = strpos($url, $frontend['base_url']);

        if ($position === false) {
            return $this->returnErrorResponse(
                'The Api Key doesn\'t match the referer url',
                $request->query->get('callback')
            );

        }
        $path = substr($url, $position + strlen($frontend['base_url']));
        if (!$path) {
            $path = '/';
        }

        $stmt = $this->app['db']->executeQuery(
            'SELECT id FROM urls WHERE frontend_id = ? AND url = ?',
            array($frontend['id'], $path)
        );

        $data = array(
            'status' => 'success',
            'title' => '',
            'comments' => array(),
        );
        $url = $stmt->fetch();
        if ($url) {
            $commentssql = $stmt = $this->app['db']->executeQuery(
                'SELECT id, user_name, comment, date, user_email
                 FROM comments
                 WHERE url_id = ?
                 ORDER BY id DESC',
                array($url['id'])
            );
            if ($comments = $commentssql->fetchAll()) {
                for ($i = 0, $nb = count($comments); $i < $nb; ++$i) {
                    $data['comments'][] = array(
                        'id' => $comments[$i]['id'],
                        'comment' => nl2br($comments[$i]['comment']),
                        'user' => $comments[$i]['user_name'],
                        'date' => $comments[$i]['date'],
                        'gravatar' => 'http://www.gravatar.com/avatar/'
                          . md5($comments[$i]['user_email']),
                    );
                }
            }
        }

        $callback = $request->query->get('callback');
        $response = new JsonResponse();
        $response->setCallback($callback);
        $response->setData($data);
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

    /**
     * Return error response.
     */
    function returnErrorResponse($error, $callback = null) {
        $response = new JsonResponse();
        $response->headers->set('Access-Control-Allow-Origin', '*');

        if ($callback) {
            $response->setCallback($callback);
        }

        $response->setData(array(
            'status' => 'error',
            'message' => $error,
        ));
        return $response;
    }

    /**
     * Valid the add comment query.
     *
     * @param Symfony\Component\HttpFoundation\Request
     *   The request.
     *
     * @return Boolean
     *   If the request is valid.
     */
    protected function isValidCommentRequest(Request $request) {
        if (!$request->get('key') || !$request->get('newComment')
            || !$request->get('href')) {
            return false;
        }
        return true;
    }

    /**
     * Get a frontend by its Apikey.
     *
     * @param String $key
     *   The api key
     *
     * @return mixed
     *   False if the key doesn't match a frontend.
     *   An array if one is found
     */
    protected function getFrontendByApikey($key) {
        $stmt = $this->app['db']->executeQuery(
            'SELECT id, base_url, name, apikey FROM frontends
             WHERE apikey = ?', array($key)
        );
        return $stmt->fetch();
    }
}

