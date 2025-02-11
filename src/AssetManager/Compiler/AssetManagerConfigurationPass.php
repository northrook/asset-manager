<?php

namespace Core\AssetManager\Compiler;

use Core\Symfony\DependencyInjection\CompilerPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;

final class AssetManagerConfigurationPass extends CompilerPass
{
    public function compile( ContainerBuilder $container ) : void
    {
        dump( $this->parameterBag->all() );
    }
}
