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

$app->get('/upload', function () {

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
            "deleteUrl" => 'http://localhost/testes/labs/web/upload/'.$file->getName(),
            "deleteType" => "DELETE"
        );
    }

    return new JsonResponse(array('files' => $json));
});

$app->post('/upload/{actualFile}', function (Request $request, File $actualFile = null) {
    $files = $request->files->all();
    if (null !== $actualFile) {
        // deleta o arquivo anterior
        unlink($actualFile->getPathname());
        $files = array($files['file']);
    } else {
        $files = $files['files'];
    }
    $json = [];
    foreach ($files as $file) {

        /* @var $file UploadedFile */
        if (strpos($file->getClientMimeType(), 'zip') !== false || $file->getClientMimeType() === 'application/octet-stream') {

            $zipname = time();
            $dest = __DIR__.'\\public\\'.$zipname;
            //$file = $file->move(__DIR__.'/public/', $zipname.'.zip');

            $zip = new ZipArchive();
            $result = $zip->open($file->getPathname(), ZipArchive::CREATE);
            if (true !== $result) {
                switch ($result) {
                    case ZipArchive::ER_EXISTS:
                        $errMsg = 'File already exists.';
                        break;
                    case ZipArchive::ER_INCONS:
                        $errMsg = 'Zip archive inconsistent.';
                        break;
                    case ZipArchive::ER_INVAL:
                        $errMsg = 'Invalid argument.';
                        break;
                    case ZipArchive::ER_MEMORY:
                        $errMsg = 'Malloc failure.';
                        break;
                    case ZipArchive::ER_NOENT:
                        $errMsg = 'Invalid argument.';
                        break;
                    case ZipArchive::ER_NOZIP:
                        $errMsg = 'Not a zip archive.';
                        break;
                    case ZipArchive::ER_OPEN:
                        $errMsg = 'Can\'t open file.';
                        break;
                    case ZipArchive::ER_READ:
                        $errMsg = 'Read error.';
                        break;
                    case ZipArchive::ER_SEEK;
                        $errMsg = 'Seek error.';
                        break;
                    default:
                        $errMsg = 'Unknown error.';
                        break;
                }

                throw new \RuntimeException(sprintf('%s', $errMsg));
            }

            $zip->extractTo($dest);
            $zip->close();

            $adapter = new LocalAdapter($dest);
            $filesystem = new Filesystem($adapter);

            foreach ($filesystem->keys() as $filename) {

                $grfile = new GrFile($filename, $filesystem);

                if (strpos($grfile->getName(), '.jpg') !== false) {

                    rename($dest.'\\'.$grfile->getName(), __DIR__.'\\public\\'.$grfile->getName());

                    $json[] = array(
                        "name" => $grfile->getName(),
                        "size" => $grfile->getSize(),
                        "url" => 'http://localhost/testes/labs/web/public/'.$grfile->getName(),
                        "thumbnailUrl" => 'http://localhost/testes/labs/web/public/'.$grfile->getName(),
                        "deleteUrl" => 'http://localhost/testes/labs/web/upload/'.$grfile->getName(),
                        "deleteType" => "DELETE"
                    );
                }
            }

            // deleta pasta criada e arquivos
            rmdir($dest);
            //unlink($file->getPathname());

        } else {
            $uploaded = $file->move(__DIR__.'/public/', 'foto'.  str_pad($request->request->get('order'), 3, '0', STR_PAD_LEFT) . '.jpg');

            $json[] = array(
                "name" => $uploaded->getFilename(),
                "size" => $uploaded->getSize(),
                "info" => array(
                    'order' => $request->request->get('order')
                ),
                "url" => 'http://localhost/testes/labs/web/public/'.$uploaded->getFilename(),
                "thumbnailUrl" => 'http://localhost/testes/labs/web/public/'.$uploaded->getFilename(),
                "deleteUrl" => 'http://localhost/testes/labs/web/upload/'.$uploaded->getFilename(),
                "deleteType" => "DELETE"
            );
        }

    }

    return new JsonResponse(array('files' => $json));
})
->value('actualFile', null)
->convert('actualFile', function ($actualFile) {
    try {
        $file = new File(__DIR__.'/public/'.$actualFile);
        return $file;
    } catch (FileNotFoundException $e) {
        // ignorar se o arquivo não existir mais
    }
    return null;
});

$app->delete('/upload/{file}', function (File $file = null) {
    $files = [];
    if (null !== $file) {
        // deleta o arquivo anterior
        unlink($file->getPathname());

        $name = $file->getFilename();

        $files[] = array($name => true);
    }
    return new JsonResponse(array('files' => $files));
})
->value('file', null)
->convert('file', function ($file) {
    try {
        $file = new File(__DIR__.'/public/'.$file);
        return $file;
    } catch (FileNotFoundException $e) {
        // ignorar se o arquivo não existir mais
    }
    return null;
});


$app->run();