<?php

use Gaufrette\Adapter\Local as LocalAdapter;
use Gaufrette\File as GrFile;
use Gaufrette\Filesystem;
use Silex\Application;
use Silex\Provider\SessionServiceProvider;
use Silex\Provider\TwigServiceProvider;
use Silex\Provider\UrlGeneratorServiceProvider;
use Symfony\Component\HttpFoundation\File\Exception\FileNotFoundException;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

require __DIR__.'/../vendor/autoload.php';

$app = new Application();
$app['debug'] = true;

$app->register(new UrlGeneratorServiceProvider());
$app->register(new SessionServiceProvider());
$app->register(new TwigServiceProvider());

$app['twig.path'] = array(__DIR__.'/../templates');
//$app['twig.options'] = array('cache' => __DIR__.'/../var/cache/twig');

$app->get('/', function () use ($app) {
    return file_get_contents($app['twig.path'][0].'/upload.html');
});

$app->get('/upload/', function () {

    $adapter = new LocalAdapter(__DIR__.'/public/');
    $filesystem = new Filesystem($adapter);

    $json = [];
    foreach ($filesystem->keys() as $filename) {

        $file = new GrFile($filename, $filesystem);

        $json[] = array(
            "name" => $file->getName(),
            "size" => $file->getSize(),
            "url" => 'http://localhost/testes/labs/web/public/'.$file->getName(),
            "thumbnailUrl" => 'http://localhost/testes/labs/web/public/'.$file->getName(),
            "deleteUrl" => 'http://localhost/testes/labs/web/public/'.$file->getName().'?delete=1',
            "deleteType" => "DELETE"
        );
    }

    return new JsonResponse(array('files' => $json));
});

$app->post('/upload/{actualFile}', function (Request $request, File $actualFile = null) {
    $files = $request->files->all();
    $json = [];
    foreach ($files['files'] as $file) {

        if (null !== $actualFile) {
            // deleta o arquivo anterior
            unlink($actualFile->getPathname());
        }

        /* @var $file UploadedFile */
        $uploaded = $file->move(__DIR__.'/public/', $file->getClientOriginalName());

        $json[] = array(
            "name" => $uploaded->getFilename(),
            "size" => $uploaded->getSize(),
            "url" => 'http://localhost/testes/labs/web/public/'.$uploaded->getFilename(),
            "thumbnailUrl" => 'http://localhost/testes/labs/web/public/'.$uploaded->getFilename(),
            "deleteUrl" => 'http://localhost/testes/labs/web/public/'.$uploaded->getFilename().'?delete=1',
            "deleteType" => "DELETE"
        );
    }

    return new JsonResponse(array('files' => $json));
})
->convert('actualFile', function ($actualFile, Request $request) {
    try {
        $file = new File(__DIR__.'/public/'.$actualFile);
        return $file;
    } catch (FileNotFoundException $e) {
        // ignorar se o arquivo não existir mais
    }
    return null;
});


$app->run();