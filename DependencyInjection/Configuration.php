<?php

namespace ItBlaster\TranslationBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * This is the class that validates and merges configuration from your app/config files
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html#cookbook-bundles-extension-config-class}
 */
class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritDoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('it_blaster_translation');

        $rootNode->children()
            ->arrayNode('locales')
                ->beforeNormalization()
                    ->ifString()
                    ->then(function($v) { return preg_split('/\s*,\s*/', $v); })
                ->end()
                ->requiresAtLeastOneElement()
                ->prototype('scalar')->end()
            ->end()
            ->arrayNode('slug_locales')
                ->beforeNormalization()
                    ->ifString()
                    ->then(function($v) { return preg_split('/\s*,\s*/', $v); })
                ->end()
                ->requiresAtLeastOneElement()
                ->prototype('scalar')->end()
            ->end()
        ->end();

        return $treeBuilder;
    }
}
