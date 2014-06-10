<?php

namespace Broda\Controller;

use Exception;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Classe RestfulController
 *
 * @author Raphael Hardt <raphael.hardt@gmail.com>
 */
abstract class RestfulController
{
    /**
     *
     * @var LoggerInterface
     */
    protected $logger = null;

    public function setLogger(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    public function getLogger()
    {
        return $this->logger;
    }

    protected function createErrorResponse(Request $request, Exception $ex)
    {
        return $this->createResponse($request, $ex->getMessage(), 500);
    }

    protected function createResponse(Request $request, $content, $status = 200)
    {
        if ($format = $request->attributes->get('_format')) {
            switch ($format) {
                case 'json':
                    return new JsonResponse($content, $status);
                default:
                    return new Response($content, $status);
            }
        }
        if ($accept = $request->server->get('HTTP_ACCEPT')) {
            switch ($accept) {
                case 'application/json':
                    return new JsonResponse($content, $status);
                default:
                    return new Response($content, $status);
            }
        }
    }

    public function get(Request $request)
    {
        try {
            // processa o get e pega o retorno como objeto ou array
            $responseData = $this->doGet();

            // formata o retorno como um formato valido
            $responseObject = $this->formatOutput($responseData);

            // cria o response de acordo com o Accept do HTTP ou de acordo com o atributo _format
            return $this->createResponse($request, $responseObject, 200);

        } catch (Exception $ex) {

            return $this->createErrorResponse($request, $ex);
        }
    }

    public function post(Request $request)
    {
        try {
            // processa o get e pega o retorno como objeto ou array
            $responseData = $this->doPost($request->request->all());

            // formata o retorno como um formato valido
            $responseObject = $this->formatOutput($responseData);

            // cria o response de acordo com o Accept do HTTP ou de acordo com o atributo _format
            return $this->createResponse($request, $responseObject, 201);

        } catch (Exception $ex) {

            return $this->createErrorResponse($request, $ex);
        }
    }

    public function put(Request $request)
    {
        try {
            // processa o get e pega o retorno como objeto ou array
            $responseData = $this->doPut($request->request->all());

            // formata o retorno como um formato valido
            $responseObject = $this->formatOutput($responseData);

            // cria o response de acordo com o Accept do HTTP ou de acordo com o atributo _format
            return $this->createResponse($request, $responseObject, 200);

        } catch (Exception $ex) {

            return $this->createErrorResponse($request, $ex);
        }
    }

    public function delete(Request $request)
    {
        try {
            // processa o get e pega o retorno como objeto ou array
            $responseData = $this->doDelete();

            // formata o retorno como um formato valido
            $responseObject = $this->formatOutput($responseData);

            // cria o response de acordo com o Accept do HTTP ou de acordo com o atributo _format
            return $this->createResponse($request, $responseObject, 200);

        } catch (Exception $ex) {

            return $this->createErrorResponse($request, $ex);
        }
    }

    public function formatOutput($responseData)
    {
        return $responseData;
    }

    abstract public function doGet();

    abstract public function doPost($data);

    abstract public function doPut($data);

    abstract public function doDelete();

}
