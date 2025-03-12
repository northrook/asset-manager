<?php

declare(strict_types=1);

namespace Core;

use Core\AssetManager\{AssetConfig,
    Asset\AssetReference,
    Config\AssetRegistration,
    Exception\UnknownAssetTypeException
};
use Core\AssetManager\Interface\{AssetInterface, AssetServiceInterface};
use Cache\CachePoolTrait;
use Core\Asset\{Script, Style, Type};
use Psr\Cache\CacheItemPoolInterface;
use Psr\Log\{LoggerAwareInterface, LoggerAwareTrait, LoggerInterface};
use Stringable;
use Symfony\Component\DependencyInjection\ServiceLocator;
use RuntimeException;
use Throwable;

// #[Lazy]
class AssetManager implements LoggerAwareInterface
{
    use CachePoolTrait, LoggerAwareTrait;

    /**
     * @param AssetConfig                            $config
     * @param Pathfinder                             $pathfinder
     * @param ?ServiceLocator<AssetServiceInterface> $serviceLocator
     * @param ?CacheItemPoolInterface                $cache
     * @param ?LoggerInterface                       $logger
     */
    final public function __construct(
        public readonly AssetConfig        $config,
        protected readonly Pathfinder      $pathfinder,
        protected readonly ?ServiceLocator $serviceLocator = null,
        ?CacheItemPoolInterface            $cache = null,
        ?LoggerInterface                   $logger = null,
    ) {
        $this->setCacheAdapter( $cache, 'asset' );
        $this->logger = $logger;
    }

    /**
     * Retrieve and `build` an asset by `reference`
     *
     * @param AssetReference|string $reference
     * @param null|string           $assetID
     *
     * @return AssetInterface
     */
    final public function getAsset(
        AssetReference|string $reference,
        ?string               $assetID = null,
    ) : AssetInterface {
        $asset = $this->resolveAsset( $reference );

        return $asset->build( $assetID );
    }

    /**
     * An {@see AssetRegistration} is the base parameters set by {@see AssetConfig}.
     *
     * @param string|Stringable $asset
     *
     * @return AssetRegistration
     */
    final public function getAssetRegistration( Stringable|string $asset ) : AssetRegistration
    {
        return $this->config->getReference( AssetReference::name( $asset ) );
    }

    /**
     * @param AssetReference|string $reference
     *
     * @return AssetInterface
     *
     * @throws UnknownAssetTypeException
     */
    final protected function resolveAsset(
        AssetReference|string $reference,
    ) : AssetInterface {
        $name = AssetReference::name( $reference );

        $configuration = $this->getAssetRegistration( $name );

        // $reference = new AssetReference(
        //         $name,
        //         $configuration->getSource()
        // );

        // Generate a new TypeAsset object here using $reference
        // AssetInterface must contain a getSource - the internal question is how do we handle images?
        // ? Do we just return a single 'master' image, reduced to a manageable size, with a blurhash?
        // . Or do we pre-parse all sizes? I'm leaning to the above - master+blurhash
        // .? don't deliver a blurhash - the Framework can do that using a Service
        // : Each <image> component can then get a default srcset, or request specific sizes

        // dd( \get_defined_vars() );

        $asset = match ( $reference->type ) {
            Type::STYLE  => new Style(),
            Type::SCRIPT => new Script(),
            default      => throw new UnknownAssetTypeException( $reference ),
        };

        $cache = $this->cache instanceof CacheItemPoolInterface ? $this->cache : null;

        $asset->setDependencies(
            $reference,
            $this->pathfinder,
            $cache,
            $this->logger,
            $this->config->publicDirectory,
            $this->config->publicAssetsDirectory,
        );

        try {
            if ( $this->serviceLocator?->has( $reference->name ) ) {
                $asset = $this->serviceLocator->get( $reference->name )( $asset );
            }
        }
        catch ( Throwable $e ) {
            throw new RuntimeException( $e->getMessage(), $e->getCode(), $e );
        }

        dd( \get_defined_vars() );
        return $asset;
    }
}
