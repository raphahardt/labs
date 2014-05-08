<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Reacao\File;

use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;

/**
 * Classe Uploader
 *
 * @author Sistema13 <sistema13@furacao.com.br>
 */
class Uploader
{

    const PARTIAL = 1;
    const COMPLETE = 2;

    /**
     *
     * @var UploadedFile
     */
    protected $uploadedFile;

    /**
     *
     * @var File
     */
    protected $completeFile;

    /**
     *
     * @var Request
     */
    protected $request;

    protected $path;

    protected $validator;

    public function __construct(Request $request, UploadedFile $file, $path)
    {
        $this->uploadedFile = $file;
        $this->request = $request;
        $this->path = $path;
    }

    public function upload()
    {
        // pega o range-content (Content-Range: bytes 0-123/400)
        $range = $this->request->server->get('HTTP_CONTENT_RANGE', null);
        list(/* ignore */, $rangeFrom, $rangeTo, $rangeTotal) =
                preg_split('/[^0-9]+/', $range);

        // pega o nome do arquivo (pode estar no Content-Disposition: attachment; filename="x")
        $name = $this->request->server->get('HTTP_CONTENT_DISPOSITION',
                '"' . $this->uploadedFile->getClientOriginalName() . '"');
        $originalName = substr($name,
                strpos($name, '"') + 1,
                strrpos($name, '"') - strpos($name, '"') - 1);

        if ($this->uploadedFile->getSize() < (int)$rangeTotal) {
            // chunk
            file_put_contents(
                    $this->path . $originalName,
                    fopen($this->uploadedFile->getPathname(), 'r'),
                    FILE_APPEND
            );

            //$file = new File($this->path . $originalName);
        }
        else {
            if (is_file($this->path . $originalName)) {
                $file = new File($this->path . $originalName);
            }
            else {
                $this->completeFile = $this->uploadedFile->move($this->path, $originalName);
                return self::COMPLETE;
            }
        }
    }

}
