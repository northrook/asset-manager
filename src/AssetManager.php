<?php

namespace Core;

use Core\AssetManager\{AssetConfig, AssetLocator, AssetReference};
use Core\AssetManager\Interface\{AssetInterface, AssetManagerInterface};
use Core\Asset\{Script, Type};
use Core\Interface\PathfinderInterface;
use Psr\Log\{LoggerInterface};
use Psr\Cache\InvalidArgumentException;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\DependencyInjection\Attribute\Lazy;
use Symfony\Component\DependencyInjection\ServiceLocator;
use Symfony\Contracts\Cache\CacheInterface;
use Throwable;

#[Lazy]
class AssetManager implements AssetManagerInterface
{
    public const string REGISTERED_CACHE_KEY = 'asset_manager.registered_assets';

    public readonly AssetLocator $locator;

    protected readonly CacheInterface $cache;

    /** @var string[] */
    protected array $assetConfig;

    /** @var string[] */
    protected array $assetDirectories;

    /**
     * @param string|string[]      $assetConfig
     * @param string|string[]      $assetDirectories
     * @param string               $storageDirectory
     * @param PathfinderInterface  $pathfinder
     * @param ?ServiceLocator      $serviceLocator
     * @param null|CacheInterface  $cache
     * @param null|LoggerInterface $logger
     */
    final public function __construct(
        string|array                           $assetConfig,
        string|array                           $assetDirectories,
        protected string                       $storageDirectory,
        protected readonly PathfinderInterface $pathfinder,
        protected readonly ?ServiceLocator     $serviceLocator = null,
        ?CacheInterface                        $cache = null,
        protected readonly ?LoggerInterface    $logger = null,
    ) {
        $this->assetConfig      = (array) $assetConfig;
        $this->assetDirectories = (array) $assetDirectories;
        $this->cache            = $cache ?? new ArrayAdapter();
        $this->locator          = new AssetLocator(
            $this->getRegisteredAssets(),
            $this->storageDirectory,
            $this->pathfinder,
            $this->logger,
        );
    }

    private function getAssetConfig() : AssetConfig
    {
        $config = new AssetConfig(
            $this->assetDirectories,
            $this->pathfinder,
        );

        foreach ( $this->assetConfig as $configPath ) {
            $path = $this->pathfinder->getPath( $configPath );

            if ( $path->exists() ) {
                ( require $path->getRealPath() )( $config );
            }
        }
        return $config;
    }

    /**
     * @return array<string, string[]>
     */
    private function getRegisteredAssets() : array
    {
        try {
            return $this->cache->get(
                $this::REGISTERED_CACHE_KEY,
                fn() => $this->getAssetConfig()->register->resolve(),
            );
        }
        catch ( InvalidArgumentException $e ) {
            throw new \InvalidArgumentException( $e->getMessage() );
        }
    }

    private function resolveAsset(
        AssetReference|string $reference,
        ?string               $assetID = null,
    ) : AssetInterface {
        if ( ! $reference instanceof AssetReference ) {
            $reference = $this->getReference( $reference );
        }

        $args = [$reference, $this->pathfinder, 'dir.public.assets', $this->storageDirectory];

        $asset = match ( $reference->type ) {
            Type::SCRIPT => new Script( ...$args ),

            default => throw new \InvalidArgumentException( 'Invalid asset type: '.$reference->type ),
        };

        $asset->build( $assetID );

        return $asset;
    }

    public function getAsset(
        AssetReference|string $reference,
        ?string               $assetID = null,
        array                 $attributes = [],
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

        $asset->attributes( $attributes );

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
