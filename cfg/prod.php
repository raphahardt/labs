<?php

date_default_timezone_set('America/Sao_paulo');

// Database
$app['db.options'] = array(
    'driver' => 'mysqli',
    'host' => 'localhost',
    'dbname' => 'fastmotors',
    'user' => 'root',
    'password' => '',
    /*
      'dbname'    => 'reacaoed_main',
      'user'      => 'reacaoed_root',
      'password'  => 'hPuV4(,}#(7=', */
    'charset' => 'utf8',
);

// Assets
$app['assetic.source_path'] = __DIR__.'/public';
$app['assetic.dist_path'] = __DIR__.'/../web/assets';
$app['assetic.assets'] = require __DIR__.'/assets.php';
$app['assetic.filters'] = array(
    'jsmin' => new \Assetic\Filter\JSMinPlusFilter(),
    'cssrewrite' => new Assetic\Filter\CssRewriteFilter(),
);

// ORM
$app['orm.proxies_dir'] = __DIR__ . "/../var/orm/proxies";
$app['orm.default_cache'] = array(
    "driver" => (!function_exists('apc_fetch') ? "filesystem" : "apc"),
    "path" => __DIR__ . "/../var/orm/cache",
);
$app['orm.em.options'] = array(
    /* "query_cache" => array(
      "cache" => $default_cache,
      "path" => __DIR__."/../var/orm/query",
      ),
      "metadata_cache" => array(
      "cache" => $default_cache,
      "path" => __DIR__."/../var/orm/metadata",
      ),
      "result_cache" => array(
      "cache" => $default_cache,
      "path" => __DIR__."/../var/orm/result",
      ), */
    "mappings" => array(
        // Using actual filesystem paths
        array(
            "type" => "annotation",
            "namespace" => "Reacao\Model",
            "path" => __DIR__ . "/../src/Reacao/Model",
            "use_simple_annotation_reader" => false, // usar annotations
        ),
    ),
);

// Security
$app['security.firewalls'] = array(
    'firewall_profiler' => array(
        'pattern' => '^/_profiler.*$',
        'security' => false,
    ),
    'frw_site' => array(
        'pattern' => '^.*$',
        'form' => array('login_path' => '/login', 'check_path' => '/auth'),
        'logout' => array('logout_path' => '/logout'),
        'anonymous' => true,
        'users' => function () use ($app) {
            return $app['orm.em']->getRepository('Reacao\Model\Usuario');
        },
    ),
);
$app['security.role_hierarchy'] = array(
    'ROLE_ADMIN' => array('ROLE_COLAB', 'ROLE_JORN', 'ROLE_AUTOR'),
    'ROLE_JORN' => array('ROLE_AUTOR', 'ROLE_COLAB'),
    'ROLE_COLAB' => array('ROLE_AUTOR'),
    'ROLE_AUTOR' => array('IS_AUTHENTICATED_ANONYMOUSLY'),
);
$app['security.access_rules'] = array(
    array('^/admin', 'ROLE_ADMIN', 'http'),
    //array('^.*$', 'IS_AUTHENTICATED_ANONYMOUSLY'),
);

// Paths
$app['twig.path'] = array(__DIR__ . '/../templates');
//$app['twig.options'] = array('cache' => __DIR__.'/../var/cache/twig');

// Public
$app['public_path'] = dirname(__DIR__) . '/web/public';

$app['extractor.options'] = array(
    'to' => $app['public_path'] . '/unzip'
);