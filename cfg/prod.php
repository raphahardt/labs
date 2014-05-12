<?php

date_default_timezone_set('America/Sao_paulo');

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

// pasta
$app['upload.base_path'] = $app->share(function () {
    return dirname(__DIR__).'/web/public';
});
$app['upload.path'] = $app->share(function () use ($app) {
    $dir = $app['upload.base_path'] . '/tmp';
    if (!is_dir($dir)) {
        mkdir($dir, 0777);
    }
    return $dir;
});