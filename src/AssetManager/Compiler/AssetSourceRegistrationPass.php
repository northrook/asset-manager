<?php

namespace Core\Service\AssetManager\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

final class AssetSourceRegistrationPass implements CompilerPassInterface
{
    public function __construct()
    {
    }

    public function process( ContainerBuilder $container )
    {
        // TODO: Implement process() method.
    }
}
