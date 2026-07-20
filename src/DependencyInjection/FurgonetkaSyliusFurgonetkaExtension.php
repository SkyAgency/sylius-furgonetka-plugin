<?php

declare(strict_types=1);

namespace Furgonetka\SyliusFurgonetkaPlugin\DependencyInjection;

use Exception;
use Furgonetka\SyliusFurgonetkaPlugin\FurgonetkaSyliusFurgonetkaPlugin;
use Sylius\Bundle\CoreBundle\DependencyInjection\PrependDoctrineMigrationsTrait;
use Sylius\Bundle\CoreBundle\SyliusCoreBundle;
use Sylius\Bundle\ResourceBundle\DependencyInjection\Extension\AbstractResourceExtension;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\Yaml\Yaml;

final class FurgonetkaSyliusFurgonetkaExtension extends AbstractResourceExtension implements PrependExtensionInterface
{
    use PrependDoctrineMigrationsTrait;

    /**
     * @psalm-suppress UnusedVariable
     *
     * @throws Exception
     */
    public function load(array $configs, ContainerBuilder $container): void
    {
        $container->setParameter('furgonetka.admin.views_version', $this->getResourcesVersion());
        $container->setParameter('furgonetka.plugin_version', FurgonetkaSyliusFurgonetkaPlugin::VERSION);

        $loader = new YamlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
        $loader->load('services.yaml');

        // Sylius 1.14 (API Platform 2.7) persists API writes through a legacy DataPersister whose
        // ApiPlatform\Core interface does not exist on 2.x — register it only here. The 2.x State
        // Processor lives in the shared services.yaml.
        if ((int) SyliusCoreBundle::MAJOR_VERSION < 2) {
            $loader->load('services_v1.yaml');
        }
    }

    public function prepend(ContainerBuilder $container): void
    {
        $this->prependDoctrineMigrations($container);

        $configDir = __DIR__ . '/../Resources/config';
        $versionDir = $this->getResourcesVersion();

        // Admin view integration config:
        //  - Sylius 1.x: sylius_ui template events (Semantic UI)
        //  - Sylius 2.x: sylius_twig_hooks (Bootstrap 5)
        // plus the sylius_grid action template, per version.
        $viewsConfig = Yaml::parseFile(sprintf('%s/app/%s/views.yaml', $configDir, $versionDir));
        foreach ((array) $viewsConfig as $extension => $config) {
            $container->prependExtensionConfig($extension, $config);
        }

        // API Platform metadata format differs between 2.7 (Sylius 1.14) and 4.x (Sylius 2.x), so we
        // register only the matching version's resources. These live under api_mapping/ (NOT the
        // conventional api_resources/, which API Platform auto-scans per bundle recursively — that
        // would load BOTH versions on every Sylius and break 4.x with the 2.7 legacy format).
        $container->prependExtensionConfig('api_platform', [
            'mapping' => [
                'paths' => [sprintf('%s/api_mapping/%s', $configDir, $versionDir)],
            ],
        ]);
    }

    protected function getMigrationsNamespace(): string
    {
        return 'Furgonetka\SyliusFurgonetkaPlugin\Migrations';
    }

    protected function getMigrationsDirectory(): string
    {
        return '@FurgonetkaSyliusFurgonetkaPlugin/Migrations';
    }

    protected function getNamespacesOfMigrationsExecutedBefore(): array
    {
        return [
            'Sylius\Bundle\CoreBundle\Migrations',
        ];
    }

    private function getResourcesVersion(): string
    {
        return (int) SyliusCoreBundle::MAJOR_VERSION >= 2 ? 'v2' : 'v1';
    }
}
