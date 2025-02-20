<?php

declare(strict_types=1);

namespace Core\AssetManager\Compiler;

use Symfony\Component\DependencyInjection\{ContainerBuilder, Reference};
use Core\Symfony\DependencyInjection\CompilerPass;

/**
 */
final class RegisterAssetServices extends CompilerPass
{
    public const string ID = 'asset.service_arguments';

    public function compile( ContainerBuilder $container ) : void
    {
        if ( ! $container->hasDefinition( $this::ID ) ) {
            $service_argument = $this::ID;
            $this->console->error( $this::class." cannot find required '{$service_argument}' definition." );
            return;
        }

        $this->registerTaggedServices( $container );
    }

    private function registerTaggedServices( ContainerBuilder $container ) : void
    {
        $serviceLocatorArguments = $container->getDefinition( $this::ID )->getArguments()[0] ?? [];

        foreach ( $container->findTaggedServiceIds( $this::ID ) as $id => $unused ) {
            $taggedService = $container->getDefinition( $id );
            $serviceId     = $taggedService->innerServiceId ?? $taggedService->getClass();
            if ( $serviceId ) {
                $serviceLocatorArguments[$id] = new Reference( $serviceId );
            }
            else {
                $service_argument = $this::ID;
                $this->console->error(
                    $this::class." could not find a serviceId for '{$id}' when parsing services tagged with '{$service_argument}'.",
                );
            }
        }

        $container->getDefinition( $this::ID )->setArguments( [$serviceLocatorArguments] );
    }
}
