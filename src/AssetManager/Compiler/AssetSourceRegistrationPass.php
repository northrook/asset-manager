<?php

namespace Core\Service\AssetManager\Compiler;

use JetBrains\PhpStorm\Deprecated;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

#[Deprecated]
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
