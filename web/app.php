<?php

use Dflydev\Silex\Provider\DoctrineOrm\DoctrineOrmServiceProvider;
use Doctrine\Common\Annotations\AnnotationRegistry;
use Reacao\Controller\PublishController;
use Reacao\Provider\ImagineServiceProvider;
use Silex\Application;
use Silex\Provider\DoctrineServiceProvider;
use Silex\Provider\ServiceControllerServiceProvider;
use Silex\Provider\SessionServiceProvider;
use Silex\Provider\TwigServiceProvider;
use Silex\Provider\UrlGeneratorServiceProvider;
use Silex\Provider\ValidatorServiceProvider;
use Symfony\Component\Debug\ErrorHandler;
use Symfony\Component\Debug\ExceptionHandler;
use Symfony\Component\Finder\Finder;
use Symfony\Component\HttpFoundation\Request;

$loader = require __DIR__.'/../vendor/autoload.php';
AnnotationRegistry::registerLoader(array($loader, 'loadClass'));

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

$app = new Application();
$app['debug'] = true;

$app->register(new UrlGeneratorServiceProvider());
$app->register(new SessionServiceProvider());
$app->register(new TwigServiceProvider());
$app->register(new ServiceControllerServiceProvider());
$app->register(new ImagineServiceProvider());
$app->register(new ValidatorServiceProvider());
$app->register(new DoctrineServiceProvider(), array(
    'db.options' => array(
        'driver'    => 'mysqli',
        'host'      => 'localhost',
        'dbname'    => 'fastmotors',
        'user'      => 'root',
        'password'  => '',
        'charset'   => 'utf8',
    ),
));

$default_cache = $app['debug'] ? "array" : (!function_exists('apc_fetch') ? "filesystem" : "apc");
$app->register(new DoctrineOrmServiceProvider(), array(
    "orm.proxies_dir" => __DIR__."/../var/orm/proxies",
    "orm.default_cache" => array(
        "cache" => $default_cache,
        "path" => __DIR__."/../var/orm/cache",
    ),
    "orm.em.options" => array(
        /*"query_cache" => array(
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
        ),*/
        "mappings" => array(
            // Using actual filesystem paths
            array(
                "type" => "annotation",
                "namespace" => "Reacao\Model",
                "path" => __DIR__."/../src/Reacao/Model",
                "use_simple_annotation_reader" => false, // usar annotations
            ),
        ),
    ),
));

$app['twig.path'] = array(__DIR__.'/../templates');
//$app['twig.options'] = array('cache' => __DIR__.'/../var/cache/twig');

$app['path.public'] = __DIR__.'/public/';
$app['reacao.controller.publish'] = function () use ($app) {
    return new PublishController($app['db'], $app['request'], $app['path.public'], $app['imagine']);
};

Request::enableHttpMethodParameterOverride();
ErrorHandler::register();
ExceptionHandler::register($app['debug']);

/*
 * para tratar POSTs que vem como json
 * ver: http://silex.sensiolabs.org/doc/cookbook/json_request_body.html
 */
$app->before(function (Request $request) {
    if (0 === strpos($request->headers->get('Content-Type'), 'application/json')) {
        $data = json_decode($request->getContent(), true);
        $request->request->replace(is_array($data) ? $data : array());
    }
});

$app->get('/', function () use ($app) {
    return file_get_contents($app['twig.path'][0].'/upload.html');
});

$app->get('/upload', 'reacao.controller.publish:get');
$app->put('/upload/{id}', 'reacao.controller.publish:put');
$app->post('/upload/{id}', 'reacao.controller.publish:post')->value('id', null);
$app->delete('/upload/{id}', 'reacao.controller.publish:delete');

$app->run();