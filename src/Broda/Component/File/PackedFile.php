<?php

namespace Broda\Component\File;

use Symfony\Component\HttpFoundation\File\File;

/**
 * Description of PackedFile
 *
 * @author raphael
 */
abstract class PackedFile
{
    protected $packer;

    /** @var File */
    protected $file;
    protected $options = array();

    public function __construct(File $file, array $options)
    {
        $this->file = $file;
        $this->options = $options;
        $this->initialize();
    }

    abstract protected function initialize();
}
