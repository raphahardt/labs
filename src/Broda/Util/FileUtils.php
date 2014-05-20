<?php

namespace Broda\Util;

/**
 * Classe FileUtils
 *
 * @author Raphael Hardt <raphael.hardt@gmail.com>
 */
class FileUtils
{
    private function __construct() {}

    public static function trim($filename)
    {
        // Remove path information and dots around the filename, to prevent uploading
        // into different directories or replacing hidden system files.
        // Also remove control characters and spaces (\x00..\x20) around the filename:
        $filename = trim(basename(stripslashes($filename)), ".\x00..\x20");

        return $filename;
    }

    public static function generateFilename($extension, $checkFolder = null, $recursive = true)
    {
        $name = sha1(uniqid(mt_rand(), true)) . '.' . $extension;
        if (!empty($checkFolder)) {
            if (is_file(rtrim($checkFolder, '/') . '/' . $name)) {
                if ($recursive) {
                    // tenta gerar outro nome até encontrar um realmente único
                    return $this->generateImageName($extension, $checkFolder, $recursive);
                }
                else {
                    // arquivo ja existe, lançar exceção para ser tratada fora
                    throw new FileAlreadyExistsException(sprintf('%s already exists in folder %s',
                            $name, $checkFolder));
                }
            }
        }
        return $name;
    }

}
