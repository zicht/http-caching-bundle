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
        $maxAgeSettings = null;

        foreach ($this->urls as $settings) {
            if (preg_match($settings['pattern'], $request->getRequestUri())) {
                $maxAgeSettings = $settings;
                break;
            }
        }

        if (null !== $maxAgeSettings) {
            $cacheOptimizer = new CacheOptimizer(
                $maxAgeSettings['public'],
                $maxAgeSettings['private'],
                $maxAgeSettings['client_cache']
            );
            $cacheOptimizer->optimize($request, $response);
        }
    }
}