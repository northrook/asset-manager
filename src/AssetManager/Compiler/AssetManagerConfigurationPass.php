<?php

namespace Core\AssetManager\Compiler;

use Core\AssetManager\AssetConfig;
use Core\Symfony\DependencyInjection\CompilerPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;

final class AssetManagerConfigurationPass extends CompilerPass
{
    private AssetConfig $config;

    public function compile( ContainerBuilder $container ) : void
    {
        $this->config = new AssetConfig(
            $this->getParameterPath( 'kernel.cache_dir' ).'/assets_registered_sources.php',
            $this->getParameterPath( 'dir.assets' ),
            ['dir.core.assets' => $this->getParameterPath( 'dir.core.assets' )],
        );

        $this->parseConfigurationFiles();

        $this->config->createConfigCache();
    }

    private function parseConfigurationFiles() : void
    {
        foreach ( [
            $this->getParameterPath( 'dir.core.config', '/assets.php' ),
            $this->getParameterPath( 'dir.config', '/assets.php' ),
        ] as $config ) {
            if ( \file_exists( $config ) ) {
                ( require $config )( $this->config );
            }
        }
    }
}
