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
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\Security\Core\Encoder\EncoderFactoryInterface;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Encoder\XmlEncoder;
use Symfony\Component\Serializer\Normalizer\GetSetMethodNormalizer;
use Symfony\Component\Serializer\Serializer;

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

    public function __construct(EntityManager $em, EncoderFactoryInterface $encoder)
    {
        $this->em = $em;
        $this->encoderFactory = $encoder;
    }

    public function converter($user, Request $request)
    {
        $id = (int)$request->attributes->get('id');
        return $this->em->getRepository(get_class(new Usuario))->find($id);
    }

    public function formatter($user, $format)
    {
        $normalizer = new GetSetMethodNormalizer();
        $normalizer->setIgnoredAttributes(array('salt', 'password', 'roles'));
        $serializer = new Serializer(array($normalizer), array(new JsonEncoder(), new XmlEncoder()));

        if ($user instanceof Usuario) {
            return $serializer->serialize($user, $format);
        } else {
            /* @var $newUser Administrador */
            $newUser = $serializer->deserialize($user, 'Reacao\Model\Usuario\Administrador', $format);
            $newUser->setPassword($this->encoderFactory->getEncoder($newUser)->encodePassword($newUser->getPassword(), $newUser->getSalt()));
            return $newUser;
        }
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

    public function all(Request $request)
    {
        $users = $this->em->getRepository(get_class(new Usuario))->findAll();
        /*foreach ($users as $user) {
            $regs[] = $this->formatter($user);
        }*/

        $format = $request->getRequestFormat();
        $data = $this->formatter($users, $format);

        return new Response($data, 200, array(
            "Content-Type" => $request->getMimeType($format))
        );
    }

    public function get(Request $request, Usuario $user = null)
    {
        if (null === $user) return new HttpException(404, 'Usuario nao existe');

        $format = $request->getRequestFormat();
        $data = $this->formatter($user, $format);

        return new Response($data, 200, array(
            "Content-Type" => $request->getMimeType($format))
        );
    }

    public function post(Request $request)
    {
        $format = $request->getRequestFormat();
        $user = $this->formatter($request->request->all(), $format);

        $this->em->persist($user);
        $this->em->flush();

        $data = $this->formatter($user, $format);

        return new Response($data, 200, array(
            "Content-Type" => $request->getMimeType($format))
        );
    }

    public function put(Request $request, Usuario $user = null)
    {
        if (null === $user) return new HttpException(404, 'Usuario nao existe');

        $format = $request->getRequestFormat();

        $user->setUsername(str_rot13($request->request->get('username')));
        $this->em->flush();

        $data = $this->formatter($user, $format);

        return new Response($data, 200, array(
            "Content-Type" => $request->getMimeType($format))
        );
    }

}
