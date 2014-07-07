<?php

namespace Broda\Component\FileExtractor\Adapter;

use Broda\Component\FileExtractor\ExtractedFile;
use Broda\Component\FileExtractor\FileExtractor;
use Symfony\Component\Finder\Finder;

/**
 * Description of AbstractAdapter
 *
 * @author raphael
 */
abstract class AbstractAdapter implements AdapterInterface
{

    /**
     *
     * @var FileExtractor
     */
    protected $fe;

    /**
     *
     * @var \SplFileInfo
     */
    protected $file;

    /**
     *
     * @var array
     */
    protected $defaultOptions = array();

    /**
     *
     * @var Finder
     */
    protected $finder;

    public function __construct(FileExtractor $fe, \SplFileInfo $file,
            array $defaultOptions)
    {
        $this->fe = $fe;
        $this->file = $file;
        $this->defaultOptions = array_merge($this->defaultOptions, $defaultOptions);
        $this->finder = new Finder();

        $this->initialize($file);
    }

    public function __destruct()
    {
        $this->destroy();
    }

    public function extract($to = null, array $options = array())
    {
        $options = array_merge($this->defaultOptions, $options);
        if (null === $to) {
            $to = $options['to'];
        }

        $to = rtrim($to, '/\\') . '/';
        $to .= substr(md5(mt_rand(0, 1000)), 0, 10);

        $this->doExtract($to);

        $extractedFiles = array();
        $files = $this->finder
                ->files()
                ->ignoreUnreadableDirs(true)
                ->in($to);

        foreach ($files as $file) {
            /* @var $file \SplFileInfo */
            if ($options['recursive'] && FileExtractor::hasAdapter($file)) {
                $extractedFiles += $this->fe->open($file)->extract($to, $options);
                // sub packed files are always temporary
                @unlink($file->getPathname());
            }
            else {
                $extractedFiles[] = new ExtractedFile($file, $options['temporary']);
            }
        }

        return $extractedFiles;
    }

    abstract protected function initialize(\SplFileInfo $file);

    abstract protected function destroy();

    abstract protected function doExtract($to);
}
