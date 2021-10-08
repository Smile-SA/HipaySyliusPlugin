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

use Sylius\Bundle\ResourceBundle\DependencyInjection\Extension\AbstractResourceExtension;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;

class SmileHipaySyliusPluginExtension extends AbstractResourceExtension
{
    private const SERVICES_DIR = __DIR__ . '/../../config/';

    public function load(array $configs, ContainerBuilder $container): void
    {
        $configs = $this->processConfiguration($this->getConfiguration([], $container), $configs);
        $loader = new YamlFileLoader($container, new FileLocator(self::SERVICES_DIR));
        $loader->load('services.yaml');

        // Create parameter for api client with config
        $container->setParameter("smile_hipay_sylius_plugin.client.username", $configs['api']['api_private_username']);
        $container->setParameter("smile_hipay_sylius_plugin.client.password", $configs['api']['api_private_password']);
        $container->setParameter(
            "smile_hipay_sylius_plugin.client.secret_passphrase",
            $configs['api']['api_secret_passphrase']
        );
        $container->setParameter("smile_hipay_sylius_plugin.client.stage", $configs['api']['stage']);
        $container->setParameter("smile_hipay_sylius_plugin.client.locale", $configs['api']['locale']);
        $container->setParameter("smile_hipay_sylius_plugin.client.notify_url", $configs['api']['notify_url']);

        $container->setParameter(
            "smile_hipay_sylius_plugin.client.moto.username",
            $configs['api_moto']['api_private_username']
        );
        $container->setParameter(
            "smile_hipay_sylius_plugin.client.moto.password",
            $configs['api_moto']['api_private_password']
        );
        $container->setParameter(
            "smile_hipay_sylius_plugin.client.moto.secret_passphrase",
            $configs['api_moto']['api_secret_passphrase']
        );
        $container->setParameter("smile_hipay_sylius_plugin.client.moto.stage", $configs['api_moto']['stage']);
        $container->setParameter("smile_hipay_sylius_plugin.client.moto.locale", $configs['api_moto']['locale']);
        $container->setParameter("smile_hipay_sylius_plugin.client.moto.notify_url", $configs['api']['notify_url']);

    }

    public function getConfiguration(array $config, ContainerBuilder $container): ConfigurationInterface
    {
        return new Configuration();
    }

    /**
     * Override alias for config root node
     * @return string
     */
    public function getAlias()
    {
        return 'sylius_hipay';
    }
}
