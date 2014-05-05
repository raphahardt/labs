<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Reacao\Controller;

use Doctrine\DBAL\Connection;
use SplFileInfo;
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

    public function __construct(Connection $db, $path)
    {
        //sleep(mt_rand(2, 5));
        $this->db = $db;
        $this->path = $path;
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
            "url" => 'http://localhost/testes/labs/web/public/' . $file->getFilename(),
            "thumbnailUrl" => 'http://localhost/testes/labs/web/public/' . $file->getFilename(),
            "deleteUrl" => 'http://localhost/testes/labs/web/upload/' . $data['id'],
            "deleteType" => "DELETE"
        );
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

            if (strpos($file->getClientMimeType(), 'zip') !== false || $file->getClientMimeType() === 'application/octet-stream') {

                $tmpZip = new TemporaryUnzipper($file, $this->path);
                $files = $tmpZip->getFiles();

                foreach ($files as $f) {
                    /* @var $f SplFileInfo */

                    if (strpos($f->getFilename(), '.jpg') !== false) {

                        $json[] = array(
                            "name" => $f->getFilename(),
                            "size" => $f->getSize(),
                            "url" => 'http://localhost/testes/labs/web/public/' . $f->getFilename(),
                            "thumbnailUrl" => 'http://localhost/testes/labs/web/public/' . $f->getFilename(),
                            "deleteUrl" => 'http://localhost/testes/labs/web/upload/' . $f->getFilename(),
                            "deleteType" => "DELETE"
                        );
                    }
                }
            }
            else {
                //$newName = 'foto'.  str_pad($request->request->get('order'), 3, '0', STR_PAD_LEFT) . '.jpg';
                $newName = mt_rand(1000000, 9999999) . '.jpg';
                $uploaded = $file->move($this->path, $newName);

                if (!$id) {
                    $affected = $this->db->executeUpdate('INSERT INTO p (bla, ordem, arquivo) VALUES (?,?,?)',
                            array(
                        $request->request->get('bla'),
                        $request->request->get('order'),
                        $newName,
                    ));

                    $id = $this->db->lastInsertId();
                }
                else {
                    $affected = $this->db->executeUpdate('UPDATE p SET bla = ?, ordem = ?, arquivo = ? WHERE id = ?',
                            array(
                        $request->request->get('bla'),
                        $request->request->get('order'),
                        $newName,
                        $id,
                    ));
                }

                $json[] = $this->formatFile(array(
                    'id' => $id,
                    'ordem' => (float)$request->request->get('order'),
                    'bla' => $request->request->get('bla'),
                    'arquivo' => $uploaded->getFilename(),
                ));
            }
        }

        return new JsonResponse(array('files' => $json));
    }

    public function delete(Request $request, $id)
    {
        $filename = $this->db->fetchColumn('SELECT arquivo FROM p WHERE id = ?',
                array((int)$id));
        $file = null;

        try {
            $file = new File($this->path . $filename);
        } catch (FileNotFoundException $e) {
            // ignorar se o arquivo não existir mais
        }

        $files = [];
        if (null !== $file) {

            $this->db->executeUpdate('DELETE FROM p WHERE id = ?', array((int)$id));

            // deleta o arquivo anterior
            unlink($file->getPathname());

            $name = $file->getFilename();

            $files[] = array($name => true);
        }
        return new JsonResponse(array('files' => $files));
    }

}
