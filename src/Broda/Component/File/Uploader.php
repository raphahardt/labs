<?php

namespace Broda\Component\File;

use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;

/**
 * Description of Uploader
 *
 * @author raphael
 */
class Uploader
{

    const PARTIAL = 1;
    const COMPLETE = 2;

    /**
     * Arquivo completo
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

    public function __construct(Request $request, $path)
    {
        $this->request = $request;
        $this->path = $path;
    }

    public function upload(UploadedFile $uploadedFile)
    {
        $this->completeFile = null;
        
        // pega o range-content (Content-Range: bytes 0-123/400)
        $range = $this->getContentRange();
        $rangeTotal = $range['total'];

        // pega o nome do arquivo (pode estar no Content-Disposition: attachment; filename="x")
        $originalName = $this->getContentDispositionFilename($uploadedFile->getClientOriginalName());

        if ($uploadedFile->getSize() < (int)$rangeTotal) {
            // chunk
            file_put_contents(
                    $this->path . $originalName, fopen($uploadedFile->getPathname(), 'r'),
                    FILE_APPEND
            );

            $parcialFile = new File($this->path . $originalName);
            $this->completeFile = $parcialFile->getSize() < (int)$rangeTotal ? null : $parcialFile;

            return null !== $this->completeFile ? self::COMPLETE : self::PARTIAL;
        }
        else {
            if (is_file($this->path . $originalName)) {
                $this->completeFile = new File($this->path . $originalName);
            }
            else {
                $this->completeFile = $uploadedFile->move($this->path, $originalName);
            }
            return self::COMPLETE;
        }
    }

    public function getCompleteFile()
    {
        return $this->completeFile;
    }

    private function getContentRange()
    {
        $range = $this->request->server->get('HTTP_CONTENT_RANGE', '');
        list(/* ignore */, $rangeFrom, $rangeTo, $rangeTotal) = preg_split('/[^0-9]+/',
                $range);

        return array(
            'from' => (int)$rangeFrom,
            'to' => (int)$rangeTo,
            'total' => (int)$rangeTotal ? : (int)$this->request->server->get('CONTENT_LENGTH')
        );
    }

    private function getContentDispositionFilename($defaultFilename = '')
    {
        $name = $this->request->server->get('HTTP_CONTENT_DISPOSITION',
                '"' . $defaultFilename . '"');
        $originalName = substr($name, strpos($name, '"') + 1,
                strrpos($name, '"') - strpos($name, '"') - 1);

        return $originalName;
    }

}
