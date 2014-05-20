<?php

namespace Broda\File\Unpacker;

use Symfony\Component\HttpFoundation\File\File;

/**
 * Interface AdapterInterface
 *
 */
interface AdapterInterface
{
    public function extract(File $file, $to);
}
