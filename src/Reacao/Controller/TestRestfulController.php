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

    public function converter($user, Request $request)
    {
        $id = (int)$request->attributes->get('id');
        return $this->em->getRepository(get_class(new Usuario))->find($id);
    }

    public function formatter(Usuario $user)
    {
        return array(
            'id' => $user->getId(),
            'username' => $user->getUsername(),
            'email' => $user->getEmail(),
            'ativo' => $user->isEnabled(),
        );
    }

    public function delete(Request $request, Usuario $user = null)
    {
        if (null === $user) return new HttpException(404, 'Usuario nao existe');
        $i = $user->getId();

        $a = array(
            $i => true
        );
        return new JsonResponse($a);
    }

    public function all()
    {
        $regs = array();
        $users = $this->em->getRepository(get_class(new Usuario))->findAll();
        foreach ($users as $user) {
            $regs[] = $this->formatter($user);
        }

        $a = $regs;
        return new JsonResponse($a);
    }

    public function get(Usuario $id = null)
    {
        if (null === $id) return new HttpException(404, 'Usuario nao existe');
        $a = $this->formatter($id);;
        return new JsonResponse($a);
    }

    public function post(Request $request)
    {
        $user = new Administrador();

        $pass = $this->ef->getEncoder($user)->encodePassword('123', $user->getSalt());
        $user->setPassword($pass);
        $user->setUsername($request->request->get('username'));
        $user->setEmail($request->request->get('username').'@admin.adm');

        $this->em->persist($user);
        $this->em->flush();

        $a = $this->formatter($user);
        return new JsonResponse($a);
    }

    public function put(Request $request, Usuario $user = null)
    {
        if (null === $user) return new HttpException(404, 'Usuario nao existe');

        $user->setUsername(str_rot13($request->request->get('username')));
        $this->em->flush();

        $a = $this->formatter($user);
        return new JsonResponse($a);
    }

}
