<?php

namespace Proj\Controller;

use Broda\Component\Routing\Annotation\Route;

/**
 * Classe TestController
 *
 * @Route("/teste")
 */
class TestController
{
    /**
     *
     * @Route("/{id}", converts={"id"="converte"})
     */
    public function teste($id = 'a')
    {
        return 'meu id é '.$id;
    }

    public static function converte($id)
    {
        return strtoupper($id);
    }
}
