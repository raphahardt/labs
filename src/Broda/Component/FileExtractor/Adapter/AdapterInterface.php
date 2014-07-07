<?php

namespace Broda\Component\FileExtractor\Adapter;

/**
 *
 * @author raphael
 */
interface AdapterInterface
{
    public function extract($to = null, array $options = array());
}
