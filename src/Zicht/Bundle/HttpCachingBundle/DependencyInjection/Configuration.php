<?php
/**
 * @author Gerard van Helden <gerard@zicht.nl>
 * @copyright Zicht online <http://zicht.nl>
 */

namespace Zicht\Bundle\HttpCachingBundle\DependencyInjection;

use \Symfony\Component\Config\Definition\Builder\TreeBuilder;
use \Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * Http Caching bundle configuration schema
 */
class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritDoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('zicht_http_caching');

        $rootNode
            ->children()
                ->arrayNode('urls')
                    ->prototype('array')->prototype('scalar')->end()->end()
                ->end()
            ->end()
        ;
        return $treeBuilder;
    }
}
