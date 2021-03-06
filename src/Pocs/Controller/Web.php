<?php

namespace Pocs\Controller;

use Symfony\Component\Form\FormError;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\User\User;

use Doctrine\DBAL\DBALException;

use Pocs\Controller\Controller;
use Pocs\Form\PocsForm;
use Pocs\Entity\Frontend;
use Pocs\Entity\Url;
use Pocs\Entity\Comment;

class Web extends Controller
{
    /**
     * Init Web controller.
     */
    public function initialize()
    {
        $this->app->before(array($this, 'before'));

        $this->app->get('/', array($this, 'home'));

        $this->app->get('/admin/', array($this, 'index'))->bind('homepage');

        $this->app->get('/login', array($this, 'login'))->bind('login');

        $this->app->match('/install', array($this, 'install'))
            ->method('GET|POST')->bind('install');

        $this->app->match('/admin/frontend/add', array($this, 'addFrontend'))
            ->method('GET|POST')
            ->bind('create_frontend');

        $this->app->get('admin/frontend/view/{id}', array($this, 'viewFrontend'))
            ->bind('view_frontend');

        $this->app->get('admin/frontend/{id}/remove', array($this, 'removeFrontend'))
            ->bind('remove_frontend');

        $this->app->get('admin/frontend/{frontend}/url/{url}/remove',
            array($this, 'removeUrl'))->bind('remove_url');

        $this->app->get('admin/frontend/{frontend}/url/{url}/comment/{comment}/remove',
            array($this, 'removeComment'))->bind('remove_comment');
    }

    /**
     * Before (checking if the app is installed).
     *
     * @param Symfony\Component\HttpFoundation\Request
     *   The request.
     */
    public function before(Request $request)
    {
        $token = $this->app['security']->getToken();
        if ($token && !preg_match('#(/login|/install)$#', $request->getRequestUri())
            && ($token->getUser() === null || $token->getUser() == 'anon.')) {
            return $this->app->redirect($this->app['url_generator']
                ->generate('login'));
        }
    }

    /**
     * Home function: Redirect to the index or to the login page.
     *
     * @return Symfony\Component\HttpFoundation\Response
     *   A Response.
     */
    public function home()
    {
        if ($this->app['security']->isGranted('ROLE_ADMIN')) {
            return $this->app->redirect($this->app['url_generator']
                ->generate('homepage'));
        } else {
            return $this->app->redirect($this->app['url_generator']
                ->generate('login'));
        }
    }

    /**
     * Index page: Display all frontends.
     *
     * @return Symfony\Component\HttpFoundation\Response
     *   A Response.
     */
    public function index()
    {
        $stmt = $this->app['db']->executeQuery(
            'SELECT id, base_url, name, apikey FROM frontends ORDER BY id'
        );
        $frontends = $stmt->fetchAll(\PDO::FETCH_CLASS, get_class(new Frontend));

        return $this->app['twig']->render('index.html.twig', array(
          'frontends' => $frontends
        ));
    }

    /**
     * Login page.
     *
     * @param Symfony\Component\HttpFoundation\Request
     *   The request.
     *
     * @return Symfony\Component\HttpFoundation\Response
     *   A Response.
     */
    public function login(Request $request)
    {
        $pocsForm = new PocsForm($this->app['form.factory']);
        $form = $pocsForm->getLoginForm();

        return $this->app['twig']->render('login.html.twig', array(
            'form'  => $form->createView(),
            'error' => $this->app['security.last_error']($request),
        ));
    }

    /**
     * Add a frontend.
     *
     * @param Symfony\Component\HttpFoundation\Request
     *   The request.
     *
     * @return Symfony\Component\HttpFoundation\Response
     *   A Response.
     */
    public function addFrontend(Request $request)
    {
        $pocsform = new pocsform($this->app['form.factory']);
        $form = $pocsform->getaddfrontendform();

        if ($request->isMethod('POST')) {
            $form->bind($request);
            if ($form->isValid()) {
                $data = $form->getData();
                if ($data['url'] && $data['name']) {
                    $apikey = $this->generateUniqueKey($data['url']);
                    try {
                        $frontend = new Frontend();
                        $frontend->importFromArray(array(
                            'base_url' => $data['url'],
                            'name' => $data['name'],
                            'apikey' => $apikey,
                        ));
                        $this->app['db']->insert(
                            'frontends', $frontend->transformInArray()
                        );
                        return $this->app->redirect($this->app['url_generator']
                            ->generate('homepage'));
                    } catch(\Exception $e) {
                        $this->app['session']->getFlashBag()
                            ->add('error', $e->getMessage());
                    }
                }
            }
        }

        return $this->app['twig']->render('create-frontend.html.twig', array(
            'form' => $form->createView(),
        ));
    }
    /**
     * Install.
     *
     * @param Symfony\Component\HttpFoundation\Request
     *   The request.
     *
     * @return Symfony\Component\HttpFoundation\Response
     *   A Response.
     */
    public function install(Request $request)
    {
        if ($this->isInstalled()) {
            return $this->app->redirect($this->app['url_generator']
                ->generate('homepage'));
        }

        $pocsform = new pocsform($this->app['form.factory']);
        $form = $pocsform->getCreateForm();
        if ($request->isMethod('POST')) {
            $form->bind($request);
            if ($form->isValid()) {
              $flagError = false;
              $data = $form->getData();
              $emailError = $this->app['validator']->validateValue(
                  $data['email'], new Assert\Email()
              );
              if (count($emailError) > 0) {
                  $flagError = true;
                  $form->get('email')->addError(
                      new FormError((string) $emailError)
                  );
              } elseif ($this->app['pocs.admins.provider']->emailExists(
                $data['email'])) {

                  $flagError = true;
                  $form->get('email')->addError(
                      new FormError('This email is already registered')
                  );
              }

              if ($data['password'] != $data['conf_password']) {
                  $flagError = true;
                  $form->get('password')->addError(
                      new FormError('The 2 password don\'t match')
                  );
                  $form->get('conf_password')->addError(
                      new FormError('The 2 password don\'t match')
                  );
              }

              if (!$flagError) {
                  $encodedPassord = $this->app['security.encoder.digest']
                      ->encodePassword($data['password'], '');

                  $user = new User($data['email'], $encodedPassord,
                      array('ROLE_ADMIN'), true, true, true, true
                  );
                  $this->app['pocs.admins.provider']->createUser($user);

                  $this->app['session']->getFlashBag()
                    ->add(
                        'success',
                        'User ' . $user->getUsername(). ' has been created successfully'
                    );
                  return $this->app->redirect('login');
              }
            } else {
                $form->addError(new FormError('The form is not valid'));
            }
        }

        return $this->app['twig']->render('create-user.html.twig', array(
            'form' => $form->createView(),
            'error' => null,
            'install' => true,
        ));
    }

    /**
     * View frontend.
     *
     * @param Symfony\Component\HttpFoundation\Request
     *   The request.
     *
     * @param int $id
     *   The frontend Id.
     *
     * @return Symfony\Component\HttpFoundation\Response
     *   A Response.
     */
    public function viewFrontend(Request $request, $id) {
        $stmt = $this->app['db']->executeQuery(
            'SELECT * FROM frontends WHERE id = ?',
            array($id)
        );
        $frontend = new Frontend();
        $urls = array();
        try {
            $sqlFront = $stmt->fetch();
            if (!is_array($sqlFront)) {
                throw new \Exception('Unknown frontend id');
            } else {
                $frontend->importFromArray($sqlFront);
            }
            $stmt = $this->app['db']->executeQuery(
                'SELECT id, frontend_id, url
                 FROM urls WHERE frontend_id = ? ORDER BY id',
                array($frontend->getId())
            );

            $urls = $stmt->fetchAll(\PDO::FETCH_CLASS, get_class(new url()));
            $frontend->setUrls($urls);

            $stmt = $this->app['db']->executeQuery(
                'SELECT c.id, c.user_name, c.user_email, c.url_id, c.date, c.comment FROM comments c, urls u
                 WHERE  u.frontend_id = ? AND u.id = c.url_id',
                array($frontend->getId())
            );
            $comments = $stmt->fetchAll(\PDO::FETCH_CLASS, get_class(new Comment));

            $frontend->addCommentsToUrls($comments);

        } catch (DBAL\Exception $e) {
            $this->app['session']->getFlashBag()->add('error', $e->getMessage());
        } catch (\Exception $e) {
            $this->app['session']->getFlashBag()->add('error', $e->getMessage());
        }

        return $this->app['twig']->render('frontend-view.html.twig', array(
            'frontend' => $frontend,
        ));
    }

    /**
     * Remove a frontend.
     *
     * @param Symfony\Component\HttpFoundation\Request
     *   The request.
     *
     * @param int $id
     *   The frontend Id.
     *
     * @return Symfony\Component\HttpFoundation\Response
     *   A Response.
     */
    public function removeFrontend(Request $request, $id) {
        try {
            $stmt = $this->app['db']->executeQuery(
                'SELECT id FROM urls WHERE frontend_id = ?',
                array($id)
            );
            $urls = $stmt->fetchAll(\PDO::FETCH_COLUMN, 0);
            if (count($urls)) {
                echo 'in';
                $stmt2 = $this->app['db']->executeQuery(
                    'DELETE FROM comments WHERE url_id IN (?)',
                    array($urls),
                    array(\Doctrine\DBAL\Connection::PARAM_INT_ARRAY)
                );
            }
            $deletedUrls = $this->app['db']->delete(
                'urls', array('frontend_id' => $id)
            );
            $this->app['db']->delete('frontends', array('id' => $id));
            $this->app['session']->getFlashBag()
                ->add('success', 'This frontend has been removed');
        } catch(\Exception $e) {
            $this->app['session']->getFlashBag()->add('error', $e->getMessage());
        }

        return $this->app->redirect($this->app['url_generator']
            ->generate('homepage')
        );
    }

    /**
     * Remove a url.
     *
     * @param Symfony\Component\HttpFoundation\Request
     *   The request.
     *
     * @param int $frontend
     *   The frontend Id.
     *
     * @param int $url
     *   The url Id.
     *
     * @return Symfony\Component\HttpFoundation\Response
     *   A Response.
     */
    public function removeUrl(Request $request, $frontend, $url)
    {
        return $this->app->redirect($this->app['url_generator']
            ->generate('view_frontend', array('id' => $frontend))
        );
    }

    /**
     * Remove a comment.
     *
     * @param Symfony\Component\HttpFoundation\Request
     *   The request.
     *
     * @param int $frontend
     *   The frontend Id.
     *
     * @param int $url
     *   The url Id.
     *
     * @param int $comment
     *   The comment Id.
     *
     * @return Symfony\Component\HttpFoundation\Response
     *   A Response.
     */
    public function removeComment(Request $request, $frontend, $url, $comment)
    {
        $deleted = $this->app['db']->delete(
            'comments', array('id' => $comment, 'url_id' => $url)
        );
        if ($deleted) {
            $this->app['session']->getFlashBag()
                ->add('success', 'The url has been correctly deleted');
        } else {
            $this->app['session']->getFlashBag()
                ->add('error', 'The url ha nots been deleted');
        }

        return $this->app->redirect($this->app['url_generator']
            ->generate('view_frontend', array('id' => $frontend))
        );
    }

    /**
     * Return if the system is already installed.
     *
     * @return Boolean
     *   if the system has been installed or not.
     */
    protected function isInstalled()
    {
        $count = $this->app['db']->executeQuery(
            'SELECT * FROM admins'
        )->rowCount();

        return $count ? true : false;
    }

    /**
     * Generate a api key for a given url.
     *
     * @param String $url
     *   The url that need an apikey.
     *
     * @return String
     *   The apikey.
     */
    /**
     * Create a apikey
     */
    protected function generateUniqueKey($url) {
        return base64_encode($url . mt_rand(55, 5555));
    }
}

