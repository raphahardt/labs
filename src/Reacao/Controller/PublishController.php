<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Reacao\Controller;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DBALException;
use Imagine\Image\Box;
use Imagine\Image\ImagineInterface;
use Imagine\Image\Point;
use Psr\Log\LoggerInterface;
use Reacao\Exception\FileAlreadyExistsException;
use Symfony\Component\HttpFoundation\File\Exception\FileNotFoundException;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use TemporaryUnzipper;

/**
 * Classe PublishController
 *
 * @author Raphael Hardt <raphael.hardt@gmail.com>
 */
class PublishController
{

    /**
     *
     * @var Connection
     */
    protected $db;
    protected $path = '';
    protected $basePath = '';

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

    public function __construct(Connection $db, Request $request, $path,
            ImagineInterface $imagine)
    {
        //sleep(mt_rand(2, 5));
        $this->db = $db;
        $this->path = $path;
        $this->basePath = $request->getSchemeAndHttpHost() . $request->getBasePath() . '/';
        $this->imagine = $imagine;
    }

    public function setLogger(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    protected function formatFile(array $data)
    {
        try {
            $file = new File($this->path . $data['arquivo']);
        } catch (FileNotFoundException $e) {
            // arquivos nao encontrados são mostrados outra imagem no lugar (não da erro)
            $file = new File($this->path . '../404.jpg');
        }

        return array(
            "id" => (int)$data['id'],
            "name" => $file->getFilename(),
            "size" => $file->getSize(),
            "info" => array(
                'order' => (float)$data['ordem'],
                'bla' => $data['bla'],
            ),
            "url" => $this->basePath . 'public/' . $file->getFilename(),
            "thumbnailUrl" => $this->basePath . 'public/' . $file->getFilename(),
            "deleteUrl" => $this->basePath . 'upload/' . $data['id'],
            "deleteType" => "DELETE"
        );
    }

    /**
     * Gera um nome único para um arquivo, baseado em sua extensão.
     *
     * O nome é aleatório com 7 digitos. Se quiser aumentar a confiabilidade
     * de que o nome gerado não irá substituir outro arquivo com nome igual,
     * passe um path no $checkFolder.
     *
     * @param string $extension Extensão do arquivo (ex: jpg, gif, png)
     * @param string $checkFolder Caso informado, checará se o nome gerado já não existe
     *                             na pasta e gera outro até que o nome seja único
     *                            (se $recursive for TRUE)
     * @param bool $recursive Se TRUE, a função irá rodar até encontrar um nome único
     *                        (só funciona com $checkFolder)
     *
     * @return string Nome único para arquivo
     */
    protected function generateImageName($extension = 'jpg', $checkFolder = null,
            $recursive = true)
    {
        $name = mt_rand(1000000, 9999999) . '.' . $extension;
        if (!empty($checkFolder)) {
            if (is_file(rtrim($checkFolder, '/') . '/' . $name)) {
                if ($recursive) {
                    // tenta gerar outro nome até encontrar um realmente único
                    return $this->generateImageName($extension, $checkFolder, $recursive);
                }
                else {
                    // arquivo ja existe, lançar exceção para ser tratada fora
                    throw new FileAlreadyExistsException(sprintf('%s already exists in folder %s',
                            $name, $checkFolder));
                }
            }
        }
        return $name;
    }

    /**
     * Move as imagens para a pasta correta.
     *
     * Caso o arquivo seja uma instancia de UploadedFile, ela será
     * upada ao servidor na pasta correta. Se for um \SplFileInfo,
     * ela será movida apenas caso esteja numa subpasta criada
     * pelo descompactador de arquivos zip/rar.
     *
     * @param UploadedFile[]|\SplFileInfo[] $files Arquivos a serem movidos para a pasta do user
     * @param float $orderInitial Ordenação individual que veio do request
     * @return File[] Array de arquivos a serem processados.
     *                  Cada arquivo terá uma order + 0.01, o que permite que um único arquivo
     *                  upado possa gerar até 99 arquivos preservando a ordenação correta.
     * @throws \InvalidArgumentException Caso $files não seja um array de \SplFileInfo
     */
    protected function moveImages(array $files, $orderInitial = 0)
    {
        $returnFiles = array();
        foreach ($files as $file) {
            if ($file instanceof UploadedFile) {
                $newName = $this->generateImageName($file->getClientOriginalExtension());
                $file = $file->move($this->path, $newName);
            }

            if ($file instanceof \SplFileInfo) {
                $src = rtrim($file->getPath(), '/') . '/';
                $dest = rtrim($this->path, '/') . '/';

                $zipFolder = str_replace($dest, '', $src);

                // se o arquivo estiver em alguma subpasta (criada pelo zip)
                // move-lo para a pasta raiz e apagar a pasta zip
                if ($zipFolder) {
                    rename(
                            $src . $file->getFilename(), $dest . $file->getFilename()
                    );
                    $file = new File($dest . $file->getFilename());
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

    public function get()
    {
        $files = $this->db->fetchAll('SELECT * FROM p ORDER BY ordem ASC');

        $json = array();
        foreach ($files as $p) {
            $json[] = $this->formatFile($p);
        }

        return new JsonResponse(array('files' => $json));
    }

    public function put(Request $request, $id)
    {
        $id = (int)$id;
        $this->db->executeUpdate('UPDATE p SET bla = ?, ordem = ? WHERE id = ?',
                array(
            $request->request->get('bla'),
            $request->request->get('order'),
            $id,
        ));

        $data = $this->db->fetchAssoc('SELECT * FROM p WHERE id = ?', array($id));

        return new JsonResponse($this->formatFile($data));
    }

    public function post(Request $request, $id = null)
    {
        $uploadedFiles = $request->files->all();
        $id = (int)$id;

        if ($id) {
            $uploadedFiles = array($uploadedFiles['file']);

            // procura na tabela o registro a alterar
            $filename = $this->db->fetchColumn('SELECT arquivo FROM p WHERE id = ?',
                    array($id));

            // deleta o arquivo anterior
            unlink($this->path . $filename);
        }
        else {
            $uploadedFiles = $uploadedFiles['files'];
        }

        $json = array();
        foreach ($uploadedFiles as $file) {
            /* @var $file UploadedFile */

            $processFiles = array();
            if (strpos($file->getClientMimeType(), 'zip') !== false || $file->getClientMimeType() === 'application/octet-stream') {
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
                if (!$id) {
                    $affected = $this->db->executeUpdate('INSERT INTO p (bla, ordem, arquivo) VALUES (?,?,?)',
                            array(
                        $request->request->get('bla'),
                        $img['order'],
                        $img['name'],
                    ));

                    $id = $this->db->lastInsertId();
                }
                else {
                    $affected = $this->db->executeUpdate('UPDATE p SET bla = ?, ordem = ?, arquivo = ? WHERE id = ?',
                            array(
                        $request->request->get('bla'),
                        $img['order'],
                        $img['name'],
                        $id,
                    ));
                }

                $json[] = $this->formatFile(array(
                    'id' => $id,
                    'ordem' => (float)$img['order'],
                    'bla' => $request->request->get('bla'),
                    'arquivo' => $img['name'],
                ));

                // evita que se salve na proxima imagem caso tenha duas a serem alteradas
                // uma deve sempre fazer o update, a outra o insert
                $id = null;
            }
        }

        return new JsonResponse(array('files' => $json));
    }

    public function delete(Request $request, $id)
    {
        $files = array();
        try {
            $filename = $this->db->fetchColumn('SELECT arquivo FROM p WHERE id = ?',
                    array((int)$id));

            try {
                $file = new File($this->path . $filename);

                // deleta o arquivo
                unlink($file->getPathname());

            } catch (FileNotFoundException $e) {
                // ignorar se o arquivo não existir mais
                if (null !== $this->logger) {
                    $this->logger->notice('Arquivo {file} já não existia '
                            . 'antes de ser deletado pelo usuário',
                            array('file' => $this->path . $filename));
                }
            }

            $this->db->executeUpdate('DELETE FROM p WHERE id = ?', array((int)$id));

            $files[] = array($filename => true);

        } catch (DBALException $ex) {
            if (null !== $this->logger) {
                $this->logger->alert($ex);
            }
        }

        return new JsonResponse(array('files' => $files));
    }

}
