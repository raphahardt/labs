<?php

namespace Broda\Model;

/**
 * Classe AbstractModel
 *
 */
class AbstractModel
{
    static public function getClass()
    {
        return get_class(new static);
    }
}
