<?php

namespace Broda\Component\FileExtractor;

/**
 * Description of ExtractedFile
 *
 * @author raphael
 */
class ExtractedFile
{

    /**
     *
     * @var \SplFileInfo
     */
    protected $file;
    protected $unlinkOnDestroy = false;

    public function __construct(\SplFileInfo $file, $temporary = true)
    {
        $this->file = $file;
        $this->unlinkOnDestroy = (bool)$temporary;
    }

    public function move($path)
    {
        $newLocation = rtrim($path, '/\\') . DIRECTORY_SEPARATOR . $this->file->getFilename();
        if (false === rename($this->file->getPathname(), $newLocation)) {
            throw new \RuntimeException('The file can not be moved');
        }
        $this->unlinkOnDestroy = false;
        return new \SplFileInfo($newLocation);
    }

    public function __call($name, $arguments)
    {
        return call_user_func_array(array($this->file, $name), $arguments);
    }

    public function __destruct()
    {
        if ($this->unlinkOnDestroy) {
            @unlink($this->file->getPathname());
            @rmdir(dirname($this->file->getPathname()));
        }
    }

}
