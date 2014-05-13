<?php

use Doctrine\ORM\Tools\SchemaTool;

require __DIR__.'/../src/autoload.php';

$app = require __DIR__ . '/../src/app.php';
require __DIR__ . '/../cfg/dev.php';

$namespace = $app['orm.em.options']['mappings'][0]['namespace'];
$em = $app['orm.em'];

$classes = array(
    //$em->getClassMetadata($namespace.'\Serie\Capitulo\Pagina'),
    $em->getClassMetadata($namespace.'\Usuario'),
    $em->getClassMetadata($namespace.'\Usuario\Administrador'),
    $em->getClassMetadata($namespace.'\Usuario\Colaborador'),
    $em->getClassMetadata($namespace.'\Role'),
);

$tool = new SchemaTool($em);
$tool->dropSchema($classes);
$tool->createSchema($classes);