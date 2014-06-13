<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Reacao\Controller;

use Doctrine\ORM\EntityManager;
use Reacao\Model\Usuario;
use Reacao\Model\Usuario\Administrador;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\Security\Core\Encoder\EncoderFactoryInterface;

/**
 * Classe TestRestfulController
 *
 */
class TestRestfulController
{

    /**
     *
     * @var EntityManager
     */
    protected $em;

    /**
     *
     * @var EncoderFactoryInterface
     */
    protected $ef;

    public function __construct(EntityManager $em, EncoderFactoryInterface $ef)
    {
        $this->em = $em;
        $this->ef = $ef;
    }

    public function converter($id)
    {
        return $this->em->getRepository(get_class(new Usuario))->find((int)$id);
    }

    public function delete(Request $request, Usuario $id = null)
    {
        if (null === $id) return new HttpException(404, 'Usuario nao existe');
        $i = $id->getId();

        $a = array(
            $i => true
        );
        return new JsonResponse($a);
    }

    public function all()
    {
        $regs = array();
        $users = $this->em->getRepository(get_class(new Usuario))->findAll();
        foreach ($users as $id) {
            $regs[] = array(
                'id' => $id->getId(),
                'username' => $id->getUsername(),
                'email' => $id->getEmail(),
                'ativo' => $id->isEnabled(),
            );
        }

        $a = $regs;
        return new JsonResponse($a);
    }

    public function get(Usuario $id = null)
    {
        if (null === $id) return new HttpException(404, 'Usuario nao existe');
        $a = array(
            'id' => $id->getId(),
            'username' => $id->getUsername(),
            'email' => $id->getEmail(),
            'ativo' => $id->isEnabled(),
        );
        return new JsonResponse($a);
    }

    public function post(Request $request)
    {
        $role = new Administrador();

        $pass = $this->ef->getEncoder($role)->encodePassword('123', $role->getSalt());
        $role->setPassword($pass);
        $role->setUsername($request->request->get('username'));
        $role->setEmail($request->request->get('username').'@admin.adm');

        $this->em->persist($role);
        $this->em->flush();

        $a = array(
            'id' => $role->getId(),
            'username' => $role->getUsername(),
            'email' => $role->getEmail(),
            'ativo' => $role->isEnabled(),
        );
        return new JsonResponse($a);
    }

    public function put(Request $request, Usuario $id = null)
    {
        if (null === $id) return new HttpException(404, 'Usuario nao existe');

        $id->setUsername(str_rot13($request->request->get('username')));
        $this->em->flush();

        $a = array(
            'id' => $id->getId(),
            'username' => $id->getUsername(),
            'email' => $id->getEmail(),
            'ativo' => $id->isEnabled(),
        );
        return new JsonResponse($a);
    }

}
