<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Reacao\File;

use Symfony\Component\HttpFoundation\File\File as BaseFile;

/**
 * Classe File
 *
 * @author Sistema13 <sistema13@furacao.com.br>
 */
class File extends BaseFile
{

    protected $parcial;
    protected $newName;

    public function setParcial($totalSize)
    {
        $this->parcial = $this->getSize() < $totalSize;
    }

    public function isParcial()
    {
        return $this->parcial;
    }

    public function setNewFilename($filename)
    {
        $this->newName = $filename;
    }
}
