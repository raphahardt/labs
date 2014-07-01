<?php

namespace Broda\Component\File;

use Symfony\Component\HttpFoundation\File\File;
use ZipArchive;

/**
 * Description of ZipPackedFile
 *
 * @author raphael
 */
class ZipPackedFile extends PackedFile
{
    /** @var ZipArchive */
    protected $zipArchive;

    public function extract(File $file, $to)
    {
        $this->init($file);
        $this->zipArchive->extractTo($to);
        $this->zipArchive->close();
        return true;
    }

    protected function initialize()
    {
        $this->zipArchive = new ZipArchive();

        if (true !== ($resultCode = $this->zipArchive->open($this->file->getPathname(), ZipArchive::CREATE))) {
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

            throw new \RuntimeException(sprintf('%s', $errMsg));
        }
    }
}
