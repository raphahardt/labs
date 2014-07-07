<?php

namespace Broda\Component\FileExtractor\Adapter;

use ZipArchive;

/**
 * Description of ZipAdapter
 *
 * @author raphael
 */
class ZipAdapter extends AbstractAdapter
{
    /**
     *
     * @var ZipArchive
     */
    protected $zipArchive;

    protected function initialize(\SplFileInfo $file)
    {

        if (!extension_loaded('zip')) {
            throw new \RuntimeException(sprintf(
                'Unable to use %s as the ZIP extension is not available.',
                __CLASS__
            ));
        }

        $this->zipArchive = new ZipArchive();

        if (true !== ($resultCode = $this->zipArchive->open($file->getPathname(), ZipArchive::CREATE))) {
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

    protected function destroy()
    {
        $this->zipArchive->close();
    }

    protected function doExtract($to)
    {
        $this->zipArchive->extractTo($to);
    }

}
