<?php
/**
 * @author Gerard van Helden <gerard@zicht.nl>
 * @copyright Zicht online <http://zicht.nl>
 */

namespace Zicht\Bundle\HttpCachingBundle\Http\Optimizer;

use \Symfony\Component\HttpFoundation\Request;
use \Symfony\Component\HttpFoundation\Response;

/**
 * Response optimizer implementation that adds a default lifetime to objects that have no TTL
 */
class DefaultResponseOptimizer implements ResponseOptimizerInterface
{
    /**
     * Constructor
     *
     * $urls is an array with the following format:
     * array(
     *  '!^/url-pattern!' => array(22, 55),
     *  '!^/another-url-pattern!' => array(33, 66),
     * )
     *
     * Where the url-patterns are regular expressions and the array contains the number of seconds
     * that either the public or private cache, respectively, is valid.
     *
     * @param array $urls
     */
    public function __construct($urls)
    {
        $this->urls = $urls;
    }


    /**
     * If there is no default max age defined, do nothing, otherwise add a max-age and s-maxage Cache-Control directive
     * to response that have no cache control configured.
     *
     * @param Request $request
     * @param Response $response
     * @return void
     */
    public function optimize(Request $request, Response $response)
    {
        if (
            !$response->headers->getCacheControlDirective('private')
            && !$response->headers->getCacheControlDirective('public')
            && !$response->headers->getCacheControlDirective('max-age')
            && !$response->headers->getCacheControlDirective('s-maxage')
        ) {
            $defaultMaxAge = null;

            foreach ($this->urls as $pattern => $ages) {
                if (preg_match($pattern, $request->getRequestUri())) {
                    $defaultMaxAge = $ages;
                    break;
                }
            }

            if (null !== $defaultMaxAge) {
                list($privateMaxAge, $publicMaxAge) = $defaultMaxAge;

                // If we have cookies
                if ($request->cookies->count() > 0) {
                    $response->headers->addCacheControlDirective('private');
                    if (null !== $privateMaxAge) {
                        if ($privateMaxAge < 0) {
                            // consider the response uncachable if the default is < 0
                            $response->headers->addCacheControlDirective('no-cache');
                            $response->headers->addCacheControlDirective('must-revalidate');
                            $response->headers->addCacheControlDirective('max-age', 0);
                        } else {
                            $response->headers->addCacheControlDirective('max-age', $privateMaxAge);
                        }
                    }
                } else {
                    $response->headers->addCacheControlDirective('public');
                    $response->headers->addCacheControlDirective('max-age', $publicMaxAge);
                    $response->headers->addCacheControlDirective('s-maxage', $publicMaxAge);
                }
            }
        }
    }
}