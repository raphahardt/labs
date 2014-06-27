<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Reacao\Controller;

use Broda\Component\Rest\RestResponse;
use Broda\Component\Rest\RestService;
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
    protected $encoderFactory;

    /**
     *
     * @var RestService
     */
    protected $rest;

    public function __construct(EntityManager $em, EncoderFactoryInterface $encoder, RestService $rest)
    {
        $this->em = $em;
        $this->encoderFactory = $encoder;
        $this->rest = $rest;
    }

    public function converter($user, Request $request)
    {
        $id = (int)$request->attributes->get('id');
        return $this->em->getRepository(Usuario::getClass())->find((int)$id);
    }

    public function delete(Usuario $user = null)
    {
        if (null === $user) throw new HttpException(404, 'Usuario não existe');
        $i = $user->getId();

        $this->em->remove($user);
        $this->em->flush();

        $a = array(
            $i => true
        );
        return new RestResponse($a);
    }

    public function all()
    {
        $users = $this->rest->filter(Usuario::getClass());

        return new RestResponse($users);
    }

    public function get(Usuario $user = null)
    {
        if (null === $user) throw new HttpException(404, 'Usuario não existe');

        return new RestResponse($user);
    }

    public function post(Request $request)
    {
        $user = $this->rest->createObjectFromRequest($request, Administrador::getClass());
        $user->encodePassword($this->encoderFactory);

        $this->em->persist($user);
        $this->em->flush();

        return new RestResponse($user);
    }

    public function put(Request $request, Usuario $user = null)
    {
        if (null === $user) throw new HttpException(404, 'Usuario não existe');

        $user->setUsername(str_rot13($request->request->get('username')));
        if ($user->getPassword() !== ($pass = $request->request->get('password')) && $pass) {
            $user->setPassword($pass);
            $user->encodePassword($this->encoderFactory);
        }

        $this->em->flush();

        return new RestResponse($user);
    }

}
