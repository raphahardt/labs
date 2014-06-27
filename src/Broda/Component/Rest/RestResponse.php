<?php

namespace Broda\Component\Rest;

/**
 * Classe RestResponse
 *
 * Serve para saber se terá que ser respondido em forma de RESTful ou não.
 *
 */
class RestResponse
{
    /**
     *
     * @var object
     */
    protected $data;

    public function __construct($data)
    {
        $this->data = $data;
    }

    public function getData()
    {
        return $this->data;
    }

}
