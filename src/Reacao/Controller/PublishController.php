<?php

namespace Reacao\Controller;

use Broda\File\Uploader;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\ORMException;
use Imagine\Image\Box;
use Imagine\Image\ImagineInterface;
use Imagine\Image\Point;
use Psr\Log\LoggerInterface;
use Reacao\Model\Serie\Capitulo\Pagina;
use Symfony\Component\HttpFoundation\File\Exception\FileNotFoundException;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\Validator;
use Symfony\Component\Validator\ValidatorInterface;
use TemporaryUnzipper;

/**
 * Classe PublishController
 *
 * @author Raphael Hardt <raphael.hardt@gmail.com>
 */
class PublishController
{

    protected $path = '';
    protected $basePath = '';

    /**
     *
     * @var EntityManagerInterface
     */
    protected $em;

    /**
     *
     * @var ImagineInterface
     */
    protected $imagine;

    /**
     *
     * @var LoggerInterface
     */
    protected $logger = null;

    /**
     *
     * @var ValidatorInterface
     */
    protected $validator;

    public function __construct(Request $request, $path,
            ImagineInterface $imagine, EntityManagerInterface $em)
    {
        //sleep(mt_rand(2, 5));
        $this->path = $path; // TODO: trocar o caminho por um objeto "usuário" que contem as configurações nele (tipo getFolder()..)
        $this->basePath = $request->getSchemeAndHttpHost() . $request->getBasePath() . '/';
        $this->imagine = $imagine;
        $this->em = $em;
        $this->validator = new Validator();
    }

    public function setLogger(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    protected function formatFile(Pagina $pag)
    {
        try {
            $file = new File($this->path . $pag->getFilename());
        } catch (FileNotFoundException $e) {
            // arquivos nao encontrados são mostrados outra imagem no lugar (não da erro)
            $file = new File($this->path . '../404.jpg');
        }

        return array(
            "id" => $pag->getId(),
            "name" => $file->getFilename(),
            "size" => $file->getSize(),
            "info" => array(
                'order' => $pag->getOrder(),
                'double' => $pag->isDoublePage(),
                'votable' => $pag->isVotable(),
            ),
            "url" => $this->basePath . 'public/' . $file->getFilename(),
            "thumbnailUrl" => $this->basePath . 'public/' . $file->getFilename(),
            "deleteUrl" => $this->basePath . 'upload/' . $pag->getId(),
            "deleteType" => "DELETE"
        );
    }

    /**
     * Move as imagens para a pasta correta.
     *
     * Caso o arquivo seja uma instancia de UploadedFile, ela será
     * upada ao servidor na pasta correta. Se for um \SplFileInfo,
     * ela será movida apenas caso esteja numa subpasta criada
     * pelo descompactador de arquivos zip/rar.
     *
     * @param UploadedFile[]|\SplFileInfo[] $files         Arquivos a serem movidos para a pasta do user
     * @param                         float $orderInitial  Ordenação individual que veio do request
     *
     * @return File[]  Array de arquivos a serem processados.
     *                  Cada arquivo terá uma order + 0.01, o que permite que um único arquivo
     *                  upado possa gerar até 99 arquivos preservando a ordenação correta.
     *
     * @throws \InvalidArgumentException Caso $files não seja um array de \SplFileInfo
     */
    protected function moveImages(array $files, $orderInitial = 0)
    {
        $returnFiles = array();
        foreach ($files as $file) {
            /*if ($file instanceof UploadedFile) {
                $newName = $this->generateImageName($file->getClientOriginalExtension());
                $file = $file->move($this->path, $newName);
            }*/

            if ($file instanceof \SplFileInfo) {
                $src = rtrim($file->getPath(), '/') . '/';
                $dest = rtrim($this->path, '/') . '/';

                $zipFolder = str_replace($dest, '', $src);

                $newName = $this->generateImageName($file->getExtension());

                // se o arquivo estiver em alguma subpasta (criada pelo zip)
                // move-lo para a pasta raiz e apagar a pasta zip
                if ($zipFolder) {
                    rename(
                            $src . $file->getFilename(), $dest . $newName
                    );
                    $file = new File($dest . $newName);
                    @rmdir($src); // tenta apagar pasta
                }
            }
            else {
                throw new \InvalidArgumentException('$files deve ser um array de \SplFileInfo');
            }

            // php não aceita floats como keys dos arrays
            // ver: http://www.php.net/manual/en/language.types.array.php
            $returnFiles[(string)$orderInitial] = $file;
            $orderInitial += 0.01; // para sub-ordens (por isso o campo é float na tabela)
        }
        return $returnFiles;
    }

    /**
     * Formata as imagens para o tamanho padrão, cria seus thumbnails e,
     * caso a imagem tenha largura maior que altura, ela é divida em duas
     * (detecção de página dupla)
     *
     * @param \SplFileInfo[] $files Imagens a serem formatadas
     * @return array Informação básica de cada imagem e sua ordenação, num array associativo
     *               [ name -> nomedoarquivo, order -> numerico ], [...]
     */
    protected function filterImages(array $files)
    {
        // acerta a imagem
        $imagesToSave = array();

        foreach ($files as $order => $file) {
            /* @var $file \SplFileInfo */

            if (false === strpos($file->getExtension(), 'jpg')) continue;

            // volta o valor do ordem de string para float
            $order = (float)$order;

            $image = $this->imagine->open($file->getPathname());
            $imgSize = $image->getSize();

            if ($imgSize->getWidth() > $imgSize->getHeight()) {
                // pagina dupla, dividir em duas
                $width = $imgSize->getWidth() / 2;
                $boxLeft = new Box($width, $imgSize->getHeight());
                $boxRight = new Box($width, $imgSize->getHeight());

                $imgLeft = $image->copy();
                $imgRight = $image->copy();

                $imgLeft->crop(new Point(0, 0), $boxLeft);
                $imgRight->crop(new Point($width, 0), $boxRight);

                $newNameLeft = $this->generateImageName($file->getExtension());
                $newNameRight = $this->generateImageName($file->getExtension());
                $imgLeft->resize($boxLeft->widen(800))->save($file->getPath() . '/' . $newNameLeft);
                $imgRight->resize($boxRight->widen(800))->save($file->getPath() . '/' . $newNameRight);

                // deleta o original
                unlink($file->getPathname());

                $imagesToSave[] = array(
                    'order' => $order,
                    'name' => $newNameLeft,
                );

                $imagesToSave[] = array(
                    'order' => $order + 0.001,
                    'name' => $newNameRight,
                );
            }
            else {

                $image->resize($imgSize->widen(800))->save($file->getPathname());

                $imagesToSave[] = array(
                    'order' => $order,
                    'name' => $file->getFilename(),
                );
            }
        }

        return $imagesToSave;
    }

    protected function logError(\Exception $exception)
    {
        if (null !== $this->logger) {

            switch (true) {
                case $exception instanceof ORMException:
                    $this->logger->alert($exception);
                    break;
            }
        }
    }

    public function get()
    {
        $json = array();
        try {
            $pags = $this->em->getRepository(get_class(new Pagina))->findAll();

            foreach ($pags as $p) {
                $json[] = $this->formatFile($p);
            }

            return new JsonResponse(array('files' => $json));

        } catch (\Exception $e) {
            $this->logError($e);
            return new JsonResponse(array('errors' => array($e->getMessage())));
        }

    }

    public function put(Request $request, $id)
    {
        try {
            /* @var $pag Pagina */
            $pag = $this->em->find(get_class(new Pagina), (int)$id);

            $pag->setDoublePage($request->request->get('double', false));
            $pag->setOrder($request->request->get('order', 0));
            $pag->setVotable($request->request->get('votable', true));

            $errors = $this->validator->validate($pag);
            if (count($errors) > 0) {
                $return = array();
                foreach ($errors as $error) {
                    $return[] = $error->getPropertyPath().' '.$error->getMessage();
                }

                return new JsonResponse(array('errors' => $return));

            } else {
                // nenhum erro de validação, salvar
                $this->em->flush();
            }

            return new JsonResponse($this->formatFile($pag));

        } catch (\Exception $e) {
            $this->logError($e);
            return new JsonResponse(array('errors' => array($e->getMessage())));
        }
    }

    public function post(Request $request, $id = null)
    {
        $uploadedFiles = $request->files->all();
        $id = (int)$id;

        if ($id) {
            $uploadedFiles = array($uploadedFiles['file']);
        } else {
            $uploadedFiles = $uploadedFiles['files'];
        }

        $json = array();
        try {
            foreach ($uploadedFiles as $file) {
                /* @var $file UploadedFile */

                // primeiro, detecta se o arquivo que está sendo passado
                // é um chunk ou um arquivo completo
                $uploader = new Uploader($request, $file, $this->path);
                $status = $uploader->upload();

                switch (true) {
                    case $status === Uploader::PARTIAL:

                        // se for parcial, só responder com json com nome do arquivo original
                        $json[] = array(
                            'name' => $uploader->getOriginalFilename(),
                        );

                        break;
                    case $status === Uploader::COMPLETE:

                        // pega o arquivo completo
                        $file = $uploader->getCompleteFile();

                        $processFiles = array();
                        if (strpos($file->getMimeType(), 'zip') !== false) {
                            // extract
                            $tmpZip = new TemporaryUnzipper($file, $this->path);
                            $processFiles = $tmpZip->getFiles();
                        }
                        else {
                            $processFiles[] = $file;
                        }

                        // move o(s) arquivo(s) uploadeados (no caso de zip, os arquivos extraidos
                        $movedFiles = $this->moveImages($processFiles,
                                (float)$request->request->get('order'));
                        // formata as imagens no tamanho certo e divide páginas duplas
                        $filteredFiles = $this->filterImages($movedFiles);

                        foreach ($filteredFiles as $img) {
                            /* @var $pag Pagina */
                            $pag = $id ? $this->em->find(get_class(new Pagina), $id) : new Pagina();

                            $pag->setFile($img['file']);
                            $pag->setDoublePage($img['double']);
                            $pag->setOrder($img['order']);
                            $pag->setVotable($request->request->get('votable', true));

                            $errors = $this->validator->validate($pag);
                            if (count($errors) > 0) {
                                $return = array();
                                foreach ($errors as $error) {
                                    $return[] = $error->getPropertyPath().' '.$error->getMessage();
                                }

                                return new JsonResponse(array('errors' => $return));

                            } else {
                                // nenhum erro de validação, salvar
                                $this->em->persist($pag);
                            }

                            $json[] = $this->formatFile($pag);

                            // evita que se salve na proxima imagem caso tenha duas a serem alteradas
                            // a primeira deve fazer o update, e a proxima o insert ou
                            // a primeira deve fazer o insert, e a proxima o insert
                            $id = null;
                        }
                        $this->em->flush();

                        break;
                }
            }

            return new JsonResponse(array('files' => $json));

        } catch (\Exception $e) {
            $this->logError($e);
            return new JsonResponse(array('errors' => array($e->getMessage())));
        }

    }

    public function delete(Request $request, $id)
    {
        $files = array();
        try {
            /* @var $pag Pagina */
            $pag = $this->em->getReference(get_class(new Pagina), (int)$id);

            $this->em->remove($pag);
            $this->em->flush();

            $files[] = array($id => true);

            return new JsonResponse(array('files' => $files));

        } catch (\Exception $e) {
            $this->logError($e);
            return new JsonResponse(array('errors' => array($e->getMessage())));
        }

    }

}
