<?php

use Reacao\Controller\PublishController;
use Symfony\Component\Debug\ErrorHandler;
use Symfony\Component\Debug\ExceptionHandler;
use Symfony\Component\HttpFoundation\Request;

require __DIR__.'/../src/autoload.php';

$request = Request::createFromGlobals();
$env = $request->getHost() === 'localhost' ? 'dev' : 'prod';

$app = require __DIR__.'/../src/app.php';
require __DIR__.'/../cfg/'.$env.'.php';

Request::enableHttpMethodParameterOverride();
ErrorHandler::register();
ExceptionHandler::register($app['debug']);

$app['reacao.controller.publish'] = function () use ($app) {
    return new PublishController($app['db'], $app['request'], $app['path.public'], $app['imagine'], $app['orm.em']);
};

$app['reacao.controller.testrest'] = function () use ($app) {
    return new Reacao\Controller\TestRestfulController($app['orm.em'], $app['security.encoder_factory'], $app['rest']);
};

$app['rest']
        ->resource('/rest.{_format}', 'reacao.controller.testrest')
        ->convert('user', 'reacao.controller.testrest:converter');

/*$app->get('/rest.{_format}', 'reacao.controller.testrest:get');
$app->put('/rest.{_format}', 'reacao.controller.testrest:put');
$app->post('/rest.{_format}', 'reacao.controller.testrest:post');
$app->delete('/rest.{_format}', 'reacao.controller.testrest:delete');*/

/*$app->get('/', function () use ($app) {
    return $app['twig']->render('upload.twig');
});*/

$app->get('/', function () use ($app) {
    return $app['twig']->render('bootstrap.twig');
});

$app->get('/unzip', function () use ($app) {
    /* @var $extractor Broda\Component\FileExtractor\FileExtractor */
    $extractor = $app['extractor'];

    $response = '';

    $files = $extractor->open(new \SplFileInfo(__DIR__.'/public/test.zip'))->extract();

    /*$response .= '<pre>';
    $response .= print_r($files, true);
    $response .= '</pre>';*/
    foreach ($files as $f) {
        $response .= $f->getPathname() . '<br>';
    }

    return $response;
});

$app->get('/prefetch', function () use ($app) {
    return new \Symfony\Component\HttpFoundation\JsonResponse(array(
        array('num' => 'two'),
        array('num' => 'twenty'),
        array('num' => 'thirthen'),
        array('num' => 'fourteen'),
        array('num' => 'fiveteen'),
    ));
});

$app->post('/ajax', function (Request $request) use ($app) {

    $data = require __DIR__.'/testdata.php';
    $total = count($data);

    $columns = $request->request->get('columns', array());
    $orders = $request->request->get('order', array());

    $array_sort = function (&$arr, $col, $dir = SORT_ASC) {
        $sort_col = array();
        foreach ($arr as $key => $row) {
            if (is_numeric($col)) {
                return;
            } else {
                $sort_col[$key] = $row->$col;
            }
        }
        array_multisort($sort_col, $dir, $arr);
    };

    $get_col_name = function ($colIndex) use ($columns) {
        return $columns[$colIndex]['data'] ?: $colIndex;
    };

    foreach ($columns as $col) {
        if ($col['search']['value']) {
            $search = strtolower($col['search']['value']);
            $colname = $col['data'];
            $data = array_filter($data, function ($row) use ($colname, $search) {
                if (!$colname) {
                    return false;
                }
                return (strpos(strtolower($row->$colname), $search) !== false);
            });
        }
    }

    if ($request->request->get('search[value]', null, true)) {
        $search = strtolower($request->request->get('search[value]', '', true));
        $data = array_filter($data, function ($row) use ($search) {
            $return = false;
            foreach ($row as $key => $val) {
                $return |= (strpos(strtolower($val), $search) !== false);
            }
            return $return;
        });
    }

    $i = count($orders);
    while ($i--) {
        $array_sort($data, $get_col_name($orders[$i]['column']), $orders[$i]['dir'] == 'desc' ? SORT_DESC : SORT_ASC);
    }

    $totalfiltered = count($data);

    if ($request->request->get('length') > 0) {
        $data = array_slice($data, (int)$request->request->get('start'), (int)$request->request->get('length'));
    }

    foreach ($data as &$r) {
        $r->DT_RowId = $r->name;
    }
    unset($r);

    return new \Symfony\Component\HttpFoundation\JsonResponse(array(
        'draw' => (int)$request->request->get('draw'),
        'recordsTotal' => $total,
        'recordsFiltered' => $totalfiltered,
        'data' => $data
    ));
});

$app->get('/remote/{query}', function ($query = null) use ($app) {
    $response = array(
        array('num' => 'one'),
        array('num' => 'thousand'),
        array('num' => 'hundred'),
        array('num' => 'a thousand'),
    );
    $response = array_filter($response, function ($val) use ($query) {
        return false !== strpos($val['num'], $query);
    });
    return new \Symfony\Component\HttpFoundation\JsonResponse($response);
});

$app->get('/upload', 'reacao.controller.publish:get');
$app->put('/upload/{id}', 'reacao.controller.publish:put');
$app->post('/upload/{id}', 'reacao.controller.publish:post')->value('id', null);
$app->delete('/upload/{id}', 'reacao.controller.publish:delete');

$app->get('/admin', function () use ($app) {
    return 'olá, '.$app['security']->getToken()->getUser()->getUsername();
});

$app->get('/login', function (Request $request) use ($app) {
    var_dump($app['security']->isGranted('IS_AUTHENTICATED_ANONYMOUSLY'));
    var_dump($app['security']->isGranted('ROLE_ADMIN'));

    /* @var $em Doctrine\ORM\EntityManagerInterface */
    /*$em = $app['orm.em'];
    $role = new \Reacao\Model\Usuario\Administrador();
    //$role = $em->find(get_class(new \Reacao\Model\Usuario\Administrador), 1);
    $pass = $app['security.encoder_factory']->getEncoder($role)->encodePassword('123', $role->getSalt());
    $role->setPassword($pass);
    $role->setUsername('teste');
    $role->setEmail('admin@admin.adm');

    $em->persist($role);
    $em->flush();/**/

    return $app['twig']->render('login.twig', array(
        'error'         => $app['security.last_error']($request),
        'last_username' => $app['session']->get('_security.last_username'),
    ));
});

$app->error(function (\Doctrine\ORM\ORMException $e, $code) use ($app) {
    if (isset($app['logger'])) {
        $app['logger']->alert($e);
    }
});

$app->error(function (Exception $e, $code) use ($app) {
    if ($app['debug']) {
        return;
    }

    return new Response($e->getMessage(), $code);
});

$app->run($request);