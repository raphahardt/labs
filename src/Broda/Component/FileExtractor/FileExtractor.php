<?php

namespace Broda\Component\FileExtractor;

/**
 * Description of FileExtractor
 *
 * // como eu quero
$packer = new FileExtractor('to/path/base');
$zip = $packer->open(new File('/to/path.zip'), array(
    'to' => 'path/to/',
    'recursive' => false,
    'temporary' => true,
    'create_subfolders' => false,
));
$files = $zip->extract();
$packer->moveAll($files, 'move/to');
 *
 * @author raphael
 */
class FileExtractor
{
    private static $class_map = array();

    protected $options = array(
        'to' => null,
        'recursive' => true,
        'temporary' => true,
        'create_subfolders' => true,
    );

    public static function registerAdapter($extension, $class)
    {
        static::$class_map[$extension] = $class;
    }

    public static function hasAdapter($extension)
    {
        if ($extension instanceof \SplFileInfo) {
            $extension = $this->getExtension($extension);
        }
        return isset(static::$class_map[$extension]);
    }

    public function __construct(array $options)
    {
        $this->options = array_merge($this->options, $options);

        static::registerAdapter('zip', 'Broda\Component\FileExtractor\Adapter\ZipAdapter');
        static::registerAdapter('rar', 'Broda\Component\FileExtractor\Adapter\RarAdapter');
    }

    /**
     * TODO
     *
     * @param \SplFileInfo $file
     * @return \Broda\Component\FileExtractor\Adapter\AdapterInterface
     * @throws \RuntimeException
     */
    public function open(\SplFileInfo $file)
    {
        $class = static::$class_map[$this->getExtension($file)];
        if (isset($class)) {
            return new $class($this, $file, $this->options);
        }
        throw new \RuntimeException(sprintf('%s extension is not a valid packed file', $this->getExtension($file)));
    }

    public function moveAll(array $extractedFiles, $path)
    {
        $moved = array();
        foreach ($extractedFiles as $file) {
            if ($file instanceof ExtractedFile) {
                $moved[] = $file->move($path);

            } elseif ($file instanceof \SplFileInfo) {
                $newLocation = rtrim($path, '/\\') . DIRECTORY_SEPARATOR . $file->getFilename();
                if (false === rename($file->getPathname(), $newLocation)) {
                    throw new \RuntimeException('The file can not be moved');
                }
                $moved[] = new \SplFileInfo($newLocation);
            }
        }
        return $moved;
    }

    private function getExtension(\SplFileInfo $file)
    {
        if (method_exists($file, 'getExtension')) {
            // getExtension() only exists in php >=5.3.6
            return $file->getExtension();
        }
        return pathinfo($file->getBasename(), PATHINFO_EXTENSION);
    }
}
