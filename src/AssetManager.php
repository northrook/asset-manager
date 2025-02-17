<?php

namespace Core;

use Core\AssetManager\{AssetLocator, AssetReference};
use Core\AssetManager\Interface\{AssetInterface, AssetManagerInterface, AssetServiceInterface};
use Core\Exception\NotImplementedException;
use Core\Interface\PathfinderInterface;
use Core\View\Element\Attributes;
use Psr\Cache\CacheItemPoolInterface;
use Core\Asset\{Script, Type};
use Psr\Log\{LoggerInterface};
use Support\Minify\JavaScriptMinifier;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\DependencyInjection\Attribute\Lazy;
use Symfony\Component\DependencyInjection\ServiceLocator;
use Symfony\Contracts\Cache\CacheInterface;
use Throwable;
use InvalidArgumentException;

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
     * @param string|string[]                        $assetConfig
     * @param string|string[]                        $assetDirectories
     * @param string                                 $storageDirectory
     * @param PathfinderInterface                    $pathfinder
     * @param ?ServiceLocator<AssetServiceInterface> $serviceLocator
     * @param null|CacheInterface                    $cache
     * @param ?CacheItemPoolInterface                $compilerCache
     * @param null|LoggerInterface                   $logger
     * @param string                                 $publicRootKey
     */
    final public function __construct(
        string|array                               $assetConfig,
        string|array                               $assetDirectories,
        protected string                           $storageDirectory,
        protected readonly PathfinderInterface     $pathfinder,
        protected readonly ?ServiceLocator         $serviceLocator = null,
        ?CacheInterface                            $cache = null,
        protected readonly ?CacheItemPoolInterface $compilerCache = null,
        protected readonly ?LoggerInterface        $logger = null,
        protected readonly string                  $publicRootKey = 'dir.public.assets',
    ) {
        $this->assetConfig      = (array) $assetConfig;
        $this->assetDirectories = (array) $assetDirectories;
        $this->cache            = $cache ?? new ArrayAdapter();
        // $this->locator          = new AssetLocator(
        //     $this->getRegisteredAssets(),
        //     $this->storageDirectory,
        //     $this->pathfinder,
        //     $this->logger,
        // );
    }

    final protected function resolveJavaScript( AssetReference $referencece ) : Script
    {
        $script = new Script(
            $referencece,
            $this->pathfinder,
            $this->publicRootKey,
        );

        $script->setMinifier(
            new JavaScriptMinifier( $this->compilerCache, $this->logger ),
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
