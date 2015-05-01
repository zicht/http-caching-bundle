<?php
/**
 * @author Gerard van Helden <gerard@zicht.nl>
 * @copyright Zicht online <http://zicht.nl>
 */

namespace Zicht\Bundle\HttpCachingBundle\Http\Optimizer;

use \Symfony\Component\HttpFoundation\Request;
use \Symfony\Component\HttpFoundation\Response;

/**
 * Interface for optimizer called by ResponseListener
 */
interface ResponseOptimizerInterface
{
    /**
     * Optimize the response.
     *
     * @param Request $request
     * @param Response $response
     * @return mixed
     */
    public function optimize(Request $request, Response $response);
}