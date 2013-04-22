<?php

use Silex\Provider\FormServiceProvider;
use Silex\Provider\HttpCacheServiceProvider;
use Silex\Provider\MonologServiceProvider;
use Silex\Provider\SecurityServiceProvider;
use Silex\Provider\RememberMeServiceProvider;
use Silex\Provider\SessionServiceProvider;
use Silex\Provider\TranslationServiceProvider;
use Silex\Provider\TwigServiceProvider;
use Silex\Provider\UrlGeneratorServiceProvider;
use Silex\Provider\ValidatorServiceProvider;
use Silex\Provider;

use SilexAssetic\AsseticServiceProvider;

use Symfony\Component\Security\Core\Encoder\Pbkdf2PasswordEncoder;
use Symfony\Component\Translation\Loader\YamlFileLoader;

use Pocs\Provider\PocsAdminsProvider;
use Pocs\Provider\PocsCommentsProvider;
use Pocs\Provider\PocsFrontendsProvider;

$app->register(new HttpCacheServiceProvider());

$app->register(new SessionServiceProvider());
$app->register(new ValidatorServiceProvider());
$app->register(new FormServiceProvider());
$app->register(new UrlGeneratorServiceProvider());
$app->register(new Silex\Provider\DoctrineServiceProvider());

$app['security.encoder.digest'] = $app->share(function ($app) {
    return new Pbkdf2PasswordEncoder();
});

$app['pocs.admins.provider'] = $app->share(function () use ($app) {
    return new PocsAdminsProvider($app['db']);
});

$app->register(new SecurityServiceProvider(), array(
    'security.firewalls' => array(
        'api' => array(
            'pattern' => '^/api',
            'security' => false,
        ),
        'install' => array(
            'pattern' => '^/install',
            'security' => true,
            'anonymous' => true,
        ),
        'login' => array(
            'pattern' => '^/login$',
            'secured' => false,
            'anonymous' => true,
        ),
        'admin' => array(
            'pattern' => '^.*$',
            'form'    => array(
                'login_path' => '/login',
                'check_path' => '/admin/login_check',
                'username_parameter' => 'form[email]',
                'password_parameter' => 'form[password]',
            ),
            'logout'    => true,
            'anonymous' => true,
            'users'     => $app['pocs.admins.provider'],
        ),
    ),
));
$app->register(new TranslationServiceProvider());
$app['translator'] = $app->share($app->extend('translator', function($translator, $app) {
    $translator->addLoader('yaml', new YamlFileLoader());

    $translator->addResource('yaml', __DIR__.'/../resources/locales/fr.yml', 'fr');

    return $translator;
}));

$app->register(new MonologServiceProvider(), array(
    'monolog.logfile' => __DIR__.'/../resources/log/app.log',
    'monolog.name'    => 'app',
    'monolog.level'   => 300 // = Logger::WARNING
));

$app->register(new TwigServiceProvider(), array(
    'twig.options'        => array(
        'cache'            => isset($app['twig.options.cache']) ? $app['twig.options.cache'] : false,
        'strict_variables' => true
    ),
    'twig.form.templates' => array('form_div_layout.html.twig', 'common/form_div_layout.html.twig'),
    'twig.path'           => array(__DIR__ . '/../resources/views')
));

if (isset($app['assetic.enabled']) && $app['assetic.enabled']) {
    $app->register(new AsseticServiceProvider(), array(
        'assetic.options' => array(
            'debug'            => $app['debug'],
            'auto_dump_assets' => $app['debug'],
        )
    ));

    $app['assetic.filter_manager'] = $app->share(
        $app->extend('assetic.filter_manager', function($fm, $app) {
            $fm->set('lessphp', new Assetic\Filter\LessphpFilter());

            return $fm;
        })
    );

    $app['assetic.asset_manager'] = $app->share(
        $app->extend('assetic.asset_manager', function($am, $app) {
            $am->set('styles', new Assetic\Asset\AssetCache(
                new Assetic\Asset\GlobAsset(
                    $app['assetic.input.path_to_css'],
                    array($app['assetic.filter_manager']->get('lessphp'))
                ),
                new Assetic\Cache\FilesystemCache($app['assetic.path_to_cache'])
            ));
            $am->get('styles')->setTargetPath($app['assetic.output.path_to_css']);

            $am->set('scripts', new Assetic\Asset\AssetCache(
                new Assetic\Asset\GlobAsset($app['assetic.input.path_to_js']),
                new Assetic\Cache\FilesystemCache($app['assetic.path_to_cache'])
            ));
            $am->get('scripts')->setTargetPath($app['assetic.output.path_to_js']);

            return $am;
        })
    );

}

// Dependency of webprofiler.
$app->register(new Provider\ServiceControllerServiceProvider());
$app->register($p = new Provider\WebProfilerServiceProvider(), array(
    'profiler.cache_dir' => __DIR__.'/../resources/cache/profiler',
));
$app->mount('/_profiler', $p);

return $app;
