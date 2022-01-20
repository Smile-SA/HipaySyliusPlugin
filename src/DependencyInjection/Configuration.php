<?php
/*
 * This file is part of the HipaySyliusPlugin
 *
 * (c) Smile <dirtech@smile.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Smile\HipaySyliusPlugin\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

final class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('sylius_hipay_plugin');

        $treeBuilder->getRootNode()
            ->children()
                ->arrayNode("api")
                    ->children()
                        ->scalarNode('api_private_username')
                            ->info("API Username for Hipay")
                            ->defaultValue("username")
                        ->end()
                        ->scalarNode('api_private_password')
                            ->info("API password for Hipay")
                            ->defaultValue("password")
                        ->end()
                        ->scalarNode('api_secret_passphrase')
                            ->info("API secret passphrase for Hipay")
                            ->defaultValue("secret_passphrase")
                        ->end()
                        ->enumNode('stage')
                            ->values(['prod', 'stage'])
                            ->info("stage used for Hipay")
                            ->defaultValue("stage")
                        ->end()
                        ->scalarNode('locale')
                            ->info("locale used for Hipay")
                            ->defaultValue("en")
                        ->end()
                        ->scalarNode('notify_url')
                            ->info("Custom notify url (mostly used for develpement)")
                            ->defaultValue('')
                        ->end()
                        ->booleanNode('do_refunds')
                            ->info("Indicates if the bundle should treat refunds request coming from Hipay notifications.")
                            ->defaultValue(true)
                        ->end()
                    ->end()
                ->end()
                ->arrayNode("api_moto")
                    ->children()
                        ->scalarNode('api_private_username')
                            ->info("API Username for Hipay MO/TO")
                            ->defaultValue("username")
                        ->end()
                        ->scalarNode('api_private_password')
                            ->info("API password for Hipay MO/TO")
                            ->defaultValue("password")
                        ->end()
                        ->scalarNode('api_secret_passphrase')
                            ->info("API secret passphrase for Hipay MO/TO")
                            ->defaultValue("secret_passphrase")
                        ->end()
                        ->enumNode('stage')
                            ->values(['prod', 'stage'])
                            ->info("stage used for Hipay MO/TO")
                            ->defaultValue("stage")
                        ->end()
                        ->scalarNode('locale')
                           ->info("locale used for Hipay MO/TO")
                           ->defaultValue("en")
                        ->end()
                        ->scalarNode('notify_url')
                            ->info("Custom notify url (mostly used for develpement)")
                            ->defaultValue('')
                        ->end()
                        ->booleanNode('do_refunds')
                            ->info("Indicates if the bundle should treat refunds request coming from Hipay notifications.")
                            ->defaultValue(true)
                        ->end()
                    ->end()
                ->end()
            ->end()
        ->end();

        return $treeBuilder;
    }
}
