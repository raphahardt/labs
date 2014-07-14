<?php

namespace Proj\Controller;

use Broda\Component\Rest\RestResponse;
use Broda\Component\Rest\RestService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Broda\Component\Rest\Annotation\Resource;
use Broda\Component\Rest\Annotation\ResourceMethod;

/**
 * Classe TestRestfulController
 *
 * @Resource("/reste", format="{_format}", service="controller.rest")
 */
class TestRestfulController
{

    /**
     *
     * @var RestService
     */
    protected $rest;

    public function __construct(RestService $rest)
    {
        $this->rest = $rest;
    }

    public function delete($id = null)
    {
        if (null === $id) throw new HttpException(404, 'Não existe');

        return new RestResponse(array());
    }

    /**
     * @ResourceMethod("all")
     */
    public function todos(Request $request)
    {
        $data = require __DIR__.'/../../../testdata.php';
        $response = $this->rest->filter($data, \Broda\Component\Rest\Filter\AbstractFilter::detectFilterByRequest($request));

        return new RestResponse($response);
    }

    public function get($id = null)
    {
        if (null === $id) throw new HttpException(404, 'Não existe');

        return new RestResponse($id);
    }

    public function post(Request $request)
    {

        return new RestResponse($user);
    }

    public function put(Request $request, $user = null)
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
