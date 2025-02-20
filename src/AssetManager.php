<?php

declare(strict_types=1);

namespace Core;

use Core\AssetManager\{AssetConfig, AssetLocator, Config\AssetReference};
use Core\AssetManager\Interface\{AssetInterface, AssetServiceInterface};
use Cache\CachePoolTrait;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Log\{LoggerAwareInterface, LoggerAwareTrait, LoggerInterface};
use Symfony\Component\DependencyInjection\ServiceLocator;

// #[Lazy]
class AssetManager implements LoggerAwareInterface
{
    use CachePoolTrait, LoggerAwareTrait;

    public readonly AssetLocator $locator;

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
        $this->cache  = $cache ?? [];
        $this->logger = $logger;
        dump( $this );
    }

    public function getAsset(
        AssetReference|string $reference,
        ?string               $assetID = null,
        // array|Attributes      $attributes = [],
    ) : ?AssetInterface {
        dump( \get_defined_vars() );
        return null;
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
        return $this->config->getReference( $asset );
    }
}
