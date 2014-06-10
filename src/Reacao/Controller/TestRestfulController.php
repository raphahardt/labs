<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Reacao\Controller;

use Broda\Controller\RestfulController;

/**
 * Classe TestRestfulController
 *
 */
class TestRestfulController extends RestfulController
{
    public function doDelete()
    {
        return array(
            'fulano' => true
        );
    }

    public function doGet()
    {
        return array(
            'nome' => 'fulano',
            'idade' => 34,
        );
    }

    public function doPost($data)
    {
        return array(
            'nome' => $data['nome'],
            'idade' => $data['idade'],
        );
    }

    public function doPut($data)
    {
        return array(
            'nome' => $data['nome'],
            'idade' => $data['idade'],
        );
    }

}
