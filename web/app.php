<?php

use Reacao\Controller\PublishController;
use Symfony\Component\Debug\ErrorHandler;
use Symfony\Component\Debug\ExceptionHandler;
use Symfony\Component\Finder\Finder;
use Symfony\Component\HttpFoundation\Request;

require __DIR__.'/../src/autoload.php';

class TemporaryUnzipper {

    protected $zipFile = '';
    protected $newFilename = '';
    protected $moveToDir = '';

    /**
     * @var ZipArchive
     */
    protected $zipArchive;

    public function __construct(SplFileInfo $zipFile, $moveToDir)
    {

        if ($zipFile->getExtension() === 'zip') {
            if (!extension_loaded('zip')) {
                throw new RuntimeException(sprintf(
                    'Unable to use %s as the ZIP extension is not available.',
                    __CLASS__
                ));
            }
        } else {
            /*throw new \RuntimeException(sprintf(
                    'Only "zip" is supported (got "%s")',
                    $zipFile->getExtension()
                ));*/
        }

        $this->newFilename = mt_rand(1000000, 9999999);
        $this->zipFile = $zipFile->getPathname();
        $this->moveToDir = rtrim($moveToDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
    }

    /**
     *
     * @return SplFileInfo[]
     */
    public function getFiles()
    {
        $zipFolder = $this->moveToDir . $this->newFilename . DIRECTORY_SEPARATOR;

        $this->init();
        $this->zipArchive->extractTo($zipFolder);
        $this->zipArchive->close();

        $finder = new Finder();
        $finder->files()->in($zipFolder);

        $files = array();
        foreach ($finder as $file) {
            /* @var $file SplFileInfo */
            /*rename(
                    $zipFolder.$file->getFilename(),
                    $this->moveToDir.$file->getFilename()
                    );

            $files[] = new SplFileInfo($this->moveToDir.$file->getFilename());*/
            $files[] = $file;
        }

        // deleta pasta criada e arquivos
        //rmdir($zipFolder);

        return $files;
    }

    protected function init()
    {
        $this->zipArchive = new ZipArchive();

        if (true !== ($resultCode = $this->zipArchive->open($this->zipFile, ZipArchive::CREATE))) {
            switch ($resultCode) {
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

            throw new RuntimeException(sprintf('%s', $errMsg));
        }

        return $this;
    }

}

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

$app->get('/', function () use ($app) {
    return file_get_contents($app['twig.path'][0].'/upload.html');
});

$app->get('/upload', 'reacao.controller.publish:get');
$app->put('/upload/{id}', 'reacao.controller.publish:put');
$app->post('/upload/{id}', 'reacao.controller.publish:post')->value('id', null);
$app->delete('/upload/{id}', 'reacao.controller.publish:delete');

$app->run();