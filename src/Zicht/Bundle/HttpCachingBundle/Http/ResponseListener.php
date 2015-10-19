<?php
/**
 * @author Gerard van Helden <gerard@zicht.nl>
 * @copyright Zicht online <http://zicht.nl>
 */
namespace Zicht\Bundle\HttpCachingBundle\Http;

use \Symfony\Component\HttpFoundation\Request;
use \Symfony\Component\HttpFoundation\Response;
use \Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use \Symfony\Component\HttpKernel\HttpKernelInterface;

use \Zicht\Bundle\HttpCachingBundle\Http\Optimizer\ResponseOptimizerInterface;


/**
 * Response listener responsible for tweaking the caching response headers
 */
class ResponseListener
{
    /**
     * Construct the listener with a response optimizer service
     *
     * @param ResponseOptimizerInterface $optimizer
     */
    public function __construct(ResponseOptimizerInterface $optimizer)
    {
        $this->responseOptimizer = $optimizer;
    }


    /**
     * Response listener implementation
     *
     * @param FilterResponseEvent $event
     * @return void
     */
    public function onKernelResponse(FilterResponseEvent $event)
    {
        if ($event->getRequestType() !== HttpKernelInterface::MASTER_REQUEST) {
            return;
        }

        if ($event->getRequest()->hasSession() && $event->getRequest()->getSession()->isStarted()) {
            if ($this->isEmpty($event->getRequest()->getSession()->all())) {
                $event->getRequest()->getSession()->clear();
                $event->getRequest()->cookies->remove(session_name());
                $event->getResponse()->headers->clearCookie(session_name());
            }
        }

        $this->optimize($event->getRequest(), $event->getResponse());
    }


    /**
     * Optimize the response that may be considered anonymous at this point.
     *
     * @param Request $request
     * @param Response $response
     * @return void
     */
    protected function optimize(Request $request, Response $response)
    {
        $this->responseOptimizer->optimize($request, $response);
    }


    /**
     * Returns true if the session may be considered "empty", i.e. all vars within the session are empty, or it
     * is an array or object consisting of only empty values.
     *
     * @param $vars
     * @return bool
     */
    protected function isEmpty($vars)
    {
        $isEmpty = false;
        if (empty($vars)) {
            $isEmpty = true;
        } elseif (is_array($vars)) {
            $isEmpty = true;
            foreach ($vars as $key => $value) {
                $isEmpty &= $this->isEmpty($value);
            }
        }
        return $isEmpty;
    }
}