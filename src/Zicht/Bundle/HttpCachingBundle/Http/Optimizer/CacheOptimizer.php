<?php
/**
 * @author Gerard van Helden <gerard@zicht.nl>
 * @copyright Zicht Online <http://zicht.nl>
 */
namespace Zicht\Bundle\HttpCachingBundle\Http\Optimizer;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class CacheOptimizer
 *
 * @package Zicht\Bundle\HttpCachingBundle\Http\Optimizer
 */
class CacheOptimizer implements ResponseOptimizerInterface
{
    /**
     * Helper method to identify this handler would even consider optimizing this request.
     *
     * @param Request $request
     * @param Response $response
     * @return bool
     */
    public static function consider(Request $request, Response $response)
    {
        return
            'GET' === $request->getMethod()
             && !(
                 $response->headers->getCacheControlDirective('private')
                 || $response->headers->getCacheControlDirective('public')
                 || $response->headers->getCacheControlDirective('max-age')
                 || $response->headers->getCacheControlDirective('s-maxage')
             )
        ;
    }


    /**
     * Construct the optimizer
     *
     * @param int $publicLifetime
     * @param int $privateLifetime
     * @param bool $allowUserAgentCaching
     */
    public function __construct($publicLifetime, $privateLifetime, $allowUserAgentCaching)
    {
        $this->publicLifetime = $publicLifetime;
        $this->privateLifetime = $privateLifetime;
        $this->allowUserAgentCaching = $allowUserAgentCaching;
    }

    /**
     * @{inheritDoc}
     */
    public function optimize(Request $request, Response $response)
    {
        if (!self::consider($request, $response)) {
            return;
        }

            // If we have cookies, never add a shared max age.
        if ($request->cookies->count() > 0 || $response->headers->getCookies()) {
            $response->headers->addCacheControlDirective('private');

            if ($response->headers->getCookies() || $this->privateLifetime < 0) {
                // consider the response uncachable if the default is < 0
                $response->headers->addCacheControlDirective('no-cache');
                $response->headers->addCacheControlDirective('must-revalidate');
                $response->headers->addCacheControlDirective('max-age', 0);

                $date = new \DateTime();
                $date->modify('-1 day');
                $response->setExpires($date);
            } else {
                $response->headers->addCacheControlDirective('max-age', $this->privateLifetime);
            }
        } else {
            $response->headers->addCacheControlDirective('public');

            // if we don't have cookies, we want to response to be cache,
            // BUT: the client must NOT cache the content locally, but revalidate with the front end proxy.
            if ($this->allowUserAgentCaching) {
                $response->headers->addCacheControlDirective('max-age', $this->publicLifetime);
            } else {
                $response->headers->addCacheControlDirective('max-age', 0);
                $response->headers->addCacheControlDirective('must-revalidate');
            }

            // The front end proxy should use this as the max age for anonymous requests.
            $response->headers->addCacheControlDirective('s-maxage', $this->publicLifetime);
        }
    }
}