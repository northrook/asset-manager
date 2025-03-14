<?php

declare(strict_types=1);

namespace Core;

use Core\AssetManager\{AssetConfig,
    AssetDefinition,
    AssetManifest,
    Compiler\AssetValidationTrait,
    Config\AssetRegistration,
    Exception\UnknownAssetRegistrationException,
    Exception\UnknownAssetTypeException,
    Exception\UnresolvedAssetException
};
use Core\AssetManager\Interface\{AssetInterface, AssetServiceInterface};
use Cache\CachePoolTrait;
use Core\Interface\LazyService;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Log\{LoggerAwareInterface, LoggerInterface};
use Stringable;
use Symfony\Component\DependencyInjection\ServiceLocator;
use RuntimeException;
use Throwable;
use const Support\AUTO;

class AssetManager implements LazyService, LoggerAwareInterface
{
    use CachePoolTrait, AssetValidationTrait;

    private readonly AssetManifest $manifest;

    /**
     * @param AssetConfig                            $config
     * @param Pathfinder                             $pathfinder
     * @param ?ServiceLocator<AssetServiceInterface> $serviceLocator
     * @param AssetManifest                          $manifest
     * @param ?CacheItemPoolInterface                $cache
     * @param ?LoggerInterface                       $logger
     */
    final public function __construct(
        public readonly AssetConfig        $config,
        protected readonly Pathfinder      $pathfinder,
        protected readonly ?ServiceLocator $serviceLocator = null,
        ?AssetManifest                     $manifest = AUTO,
        ?CacheItemPoolInterface            $cache = null,
        protected ?LoggerInterface         $logger = null,
    ) {
        $this->assignCacheAdapter( $cache, 'asset' );

        if ( $manifest ) {
            $this->manifest = $manifest;
        }
    }

    final public function getManifest() : AssetManifest
    {
        return $this->manifest ??= new AssetManifest(
            config : $this->config,
            cache  : $this->cache instanceof CacheItemPoolInterface ? $this->cache : null,
            logger : $this->logger,
        );
    }

    /**
     * Retrieve and `build` an asset.
     *
     * Accepts:
     * - `assetName` - `type.directory.asset-name`
     * - `assetReference` - 16 character hexadecimal key
     * - `assetPath` - absolute or relative path to asset
     *
     * @param string      $asset
     * @param null|string $assetID
     *
     * @return AssetInterface
     *
     * @throws UnresolvedAssetException
     */
    final public function getAsset(
        string  $asset,
        ?string $assetID = null,
    ) : AssetInterface {
        return $this
            ->resolveAsset( $asset )
            ->build( $assetID );
    }

    /**
     * An {@see AssetRegistration} is the base parameters set by {@see AssetConfig}.
     *
     * @param string|Stringable $from
     *
     * @return AssetRegistration
     * @throws UnknownAssetRegistrationException
     */
    final public function getAssetRegistration( string|Stringable $from ) : AssetRegistration
    {
        return $this->config->getReference( $this->getName( $from ) );
    }

    /**
     * @param string $from
     *
     * @return AssetInterface
     *
     * @throws UnknownAssetTypeException
     */
    final protected function resolveAsset( string $from ) : AssetInterface
    {
        // ? If $reference is exactly 16 hexadecimal characters
        // . resolve using assetReference

        $name = $this->getName( $from );

        $asset = $this->getManifest()->get( $name );

        $asset->setDependencies(
            $this->pathfinder,
            $this->cache instanceof CacheItemPoolInterface ? $this->cache : null,
            $this->logger,
        );

        try {
            if ( $this->hasTypePass( $asset ) ) {
                $this->logger?->alert(
                    'The Asset {type} has a TypePass.',
                    ['type' => $asset->type->name],
                );
            }

            if ( $this->hasServicePass( $asset ) ) {
                $asset = $this->serviceLocator?->get( $asset->name )( $asset );
            }
        }
        catch ( Throwable $e ) {
            throw new RuntimeException( $e->getMessage(), $e->getCode(), $e );
        }

        return $asset;
    }

    private function hasTypePass( AssetDefinition $asset ) : bool
    {
        return (bool) $this->serviceLocator?->has( $asset->type->name );
    }

    private function hasServicePass( AssetDefinition $asset ) : bool
    {
        return (bool) $this->serviceLocator?->has( $asset->name );
    }

    /**
     * Sets a logger.
     *
     * @internal
     *
     * @param LoggerInterface $logger
     */
    final public function setLogger( LoggerInterface $logger ) : void
    {
        $this->logger ??= $logger;
    }
}
