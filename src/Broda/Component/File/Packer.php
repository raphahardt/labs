<?php

namespace Broda\Component\File;

use Symfony\Component\HttpFoundation\File\File;


/**
 * Description of Packer
 *
 * @author raphael
 */
class Packer implements ExtractInterface, CompactInterface
{
    private $options = array(
        'to' => null,
        'recursive' => true,
        'temporary' => false,
        'create_subfolders' => true,
    );

    public function __construct($basePath, array $options = array())
    {
        $this->options = array_merge($this->options, $options);
        $this->options['to'] = $basePath;
    }

    public function open(File $file)
    {
        return new PackedFile($file, $this->options);
    }

}

// como eu quero
$packer = new Packer('to/path/base');
$zip = $packer->open(new File('/to/path.zip'), array(
    'to' => 'path/to/',
    'recursive' => false,
    'temporary' => true,
    'create_subfolders' => false,
));
$files = $zip->extract();

