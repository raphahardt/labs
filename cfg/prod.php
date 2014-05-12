<?php

date_default_timezone_set('America/Sao_paulo');

$app['db.options'] = array(
    'driver' => 'mysqli',
    'host' => 'localhost',
    'dbname' => 'fastmotors',
    'user' => 'root',
    'password' => 'lkglby90',
    /*
      'dbname'    => 'reacaoed_main',
      'user'      => 'reacaoed_root',
      'password'  => 'hPuV4(,}#(7=', */
    'charset' => 'utf8',
);

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

$app['twig.path'] = array(__DIR__.'/../templates');
//$app['twig.options'] = array('cache' => __DIR__.'/../var/cache/twig');

$app['path.public'] = __DIR__.'/public/';