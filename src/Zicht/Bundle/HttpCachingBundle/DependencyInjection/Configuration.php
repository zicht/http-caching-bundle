<?php
/**
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
                    ->beforeNormalization()
                        ->always(function($v) {
                            $ret = [];
                            // BC for the `string => [int, int]` format
                            foreach ($v as $key => $settings) {
                                if (!is_numeric($key) && is_array($settings) && count($settings) == 2) {
                                    $ret[] = [
                                        'pattern' => $key,
                                        'private' => $settings[0],
                                        'public' => $settings[1]
                                    ];
                                } else {
                                    $ret[$key] = $settings;
                                }
                            }
                            return $ret;
                        })
                    ->end()
                    ->prototype('array')
                        ->children()
                            ->scalarNode('pattern')->isRequired()->end()
                            ->scalarNode('public')->isRequired()->end()
                            ->scalarNode('private')->isRequired()->end()
                            ->booleanNode('client_cache')->defaultValue(false)->end()
                        ->end()
                    ->end()
                ->end()
            ->end()
        ;
        return $treeBuilder;
    }
}
