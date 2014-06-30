<?php

use Silex\Provider\MonologServiceProvider;
//use Silex\Provider\WebProfilerServiceProvider;

// include the prod configuration
require __DIR__.'/prod.php';

// enable the debug mode
$app['debug'] = true;

$app['orm.default_cache'] = 'array';

// ajuda a mostrar qual é o problema com o usuário
$app['security.hide_user_not_found'] = false;

$app->register(new MonologServiceProvider(), array(
    'monolog.logfile' => __DIR__.'/../var/logs/app_dev.log',
));

/*$app->register(new WebProfilerServiceProvider(), array(
    'profiler.cache_dir' => __DIR__.'/../var/cache/profiler',
));*/