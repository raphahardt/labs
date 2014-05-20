<?php

namespace Broda\File;

use Symfony\Component\HttpFoundation\File\File;

/**
 * Classe Unpacker
 *
 */
class Unpacker
{

    protected $path;

    public function __construct($path)
    {
        $this->path = $path;
    }

    public function unpack(File $packedFile)
    {

    }

}
