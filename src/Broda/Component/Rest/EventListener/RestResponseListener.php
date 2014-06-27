<?php

namespace Broda\Component\Rest\EventListener;

use Broda\Component\Rest\RestResponse;
use Broda\Component\Rest\RestService;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\GetResponseForControllerResultEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 *
 */
class RestResponseListener implements EventSubscriberInterface
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

    /**
     * Handles string responses.
     *
     * @param GetResponseForControllerResultEvent $event The event to handle
     */
    public function onKernelView(GetResponseForControllerResultEvent $event)
    {
        $response = $event->getControllerResult();
        $request = $event->getRequest();

        if ($response instanceof RestResponse) {

            $format = $request->getRequestFormat();
            $newResponse = new Response($this->rest->formatOutput($response->getData(), $format), 200, array(
                "Content-Type" => $request->getMimeType($format))
            );

            $event->setResponse($newResponse);

        }
    }

    public static function getSubscribedEvents()
    {
        return array(
            KernelEvents::VIEW => array('onKernelView', 60), // silex is -10
        );
    }
}
