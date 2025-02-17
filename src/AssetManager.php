<?php

namespace Core;

use Core\AssetManager\{AssetConfig, AssetLocator, AssetReference};
use Core\AssetManager\Interface\{AssetInterface, AssetManagerInterface, AssetServiceInterface};
use Core\Exception\NotImplementedException;
use Core\Interface\PathfinderInterface;
use Core\View\Element\Attributes;
use Psr\Cache\CacheItemPoolInterface;
use Core\Asset\{Script, Type};
use Psr\Log\{LoggerInterface};
use Support\Minify\JavaScriptMinifier;
use Symfony\Component\DependencyInjection\Attribute\Lazy;
use Symfony\Component\DependencyInjection\ServiceLocator;
use Throwable;
use InvalidArgumentException;

#[Lazy]
class AssetManager implements AssetManagerInterface
{
    public readonly AssetLocator $locator;

    /**
     * @param AssetConfig                            $config
     * @param PathfinderInterface                    $pathfinder
     * @param ?ServiceLocator<AssetServiceInterface> $serviceLocator
     * @param ?CacheItemPoolInterface                $cache
     * @param null|LoggerInterface                   $logger
     */
    final public function __construct(
        public readonly AssetConfig                $config,
        protected readonly PathfinderInterface     $pathfinder,
        protected readonly ?ServiceLocator         $serviceLocator = null,
        protected readonly ?CacheItemPoolInterface $cache = null,
        protected readonly ?LoggerInterface        $logger = null,
    ) {
        dump( $this );
    }

    final protected function resolveJavaScript( AssetReference $referencece ) : Script
    {
        $script = new Script(
            $referencece,
            $this->pathfinder,
            $this->config->publicAssetsDirectory,
        );

        $script->setMinifier(
            new JavaScriptMinifier( $this->cache, $this->logger ),
        );

        return $script;
    }

    private function resolveAsset(
        AssetReference|string $reference,
        ?string               $assetID = null,
    ) : AssetInterface {
        if ( ! $reference instanceof AssetReference ) {
            $reference = $this->getReference( $reference );
        }

        $asset = match ( $reference->type ) {
            Type::SCRIPT => $this->resolveJavaScript( $reference ),

            default => throw new InvalidArgumentException( 'Invalid asset type: '.$reference->type->name ),
        };

        if ( $this->serviceLocator?->has( $reference->name ) ) {
            $servicePass = $this->serviceLocator->get( $reference->name );

            if ( ! $servicePass instanceof AssetServiceInterface ) {
                throw new NotImplementedException(
                    $servicePass::class,
                    AssetServiceInterface::class,
                );
            }

            $asset = ( $servicePass )( $asset );
        }

        if ( $asset instanceof Script ) {
            $asset->compile( true );
        }

        $asset->build( $assetID );

        return $asset;
    }

    public function getAsset(
        AssetReference|string $reference,
        ?string               $assetID = null,
        array|Attributes      $attributes = [],
    ) : ?AssetInterface {
        try {
            $asset = $this->cache->get(
                (string) $reference,
                fn() : AssetInterface => $this->resolveAsset(
                    $reference,
                    $assetID,
                ),
            );
        }
        catch ( Throwable $e ) {
            return null;
        }

        \assert( $asset instanceof AssetInterface );
        dump(
            $asset->attributes(),
        );

        // We should probably not output any HTML here,
        // but an Object with a path to the generated file
        // we can generate HTML in either DocumentView or `northrook/assets` (extending View)
        return $asset;
    }

    /**
     * @param string  $asset
     * @param ?string $assetID
     *
     * @return AssetReference
     */
    public function getReference(
        string  $asset,
        ?string $assetID = null,
    ) : AssetReference {
        return $this->locator->getReference( $asset );
    }
}
