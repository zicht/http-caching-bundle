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
        if ('GET' !== $request->getMethod()) {
            return;
        }
        if (
            !$response->headers->getCacheControlDirective('private')
            && !$response->headers->getCacheControlDirective('public')
            && !$response->headers->getCacheControlDirective('max-age')
            && !$response->headers->getCacheControlDirective('s-maxage')
        ) {
            $maxAgeSettings = null;

            foreach ($this->urls as $settings) {
                if (preg_match($settings['pattern'], $request->getRequestUri())) {
                    $maxAgeSettings = $settings;
                    break;
                }
            }

            if (null !== $maxAgeSettings) {
                // If we have cookies, never add a shared max age.
                if ($request->cookies->count() > 0 || $response->headers->getCookies()) {
                    $response->headers->addCacheControlDirective('private');

                    if ($response->headers->getCookies() || $maxAgeSettings['private'] < 0) {
                        // consider the response uncachable if the default is < 0
                        $response->headers->addCacheControlDirective('no-cache');
                        $response->headers->addCacheControlDirective('must-revalidate');
                        $response->headers->addCacheControlDirective('max-age', 0);

                        $date = new \DateTime();
                        $date->modify('-1 day');
                        $response->setExpires($date);
                    } else {
                        $response->headers->addCacheControlDirective('max-age', $maxAgeSettings['private']);
                    }
                } else {
                    $response->headers->addCacheControlDirective('public');

                    // if we don't have cookies, we want to response to be cache,
                    // BUT: the client must NOT cache the content locally, but revalidate with the front end proxy.
                    if ($maxAgeSettings['client_cache']) {
                        $response->headers->addCacheControlDirective('max-age', $maxAgeSettings['public']);
                    } else {
                        $response->headers->addCacheControlDirective('max-age', 0);
                        $response->headers->addCacheControlDirective('must-revalidate');
                    }

                    // The front end proxy should use this as the max age for anonymous requests.
                    $response->headers->addCacheControlDirective('s-maxage', $maxAgeSettings['public']);
                }
            }
        }
    }
}