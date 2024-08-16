<?php

declare ( strict_types = 1 );

namespace Northrook;

/*
- Will fetch a provided asset, cache it, and handle optimisation.
- Will it be responsible for keeping track of assets?

- The DocumentBundle should really handle enqueuing and such, but we _could_ handle tracking here?

- We could also keep a manifest of assets, and expose that when required,
  but have the DocumentBundle decide what to ask for

*/

use Northrook\Asset\Script;
use Northrook\Asset\Stylesheet;
use Northrook\AssetManager\Asset;
use Northrook\Cache\ManifestCache;
use Northrook\Core\Trait\SingletonClass;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\Cache\CacheInterface;

final class AssetManager
{
    use SingletonClass;

    private array $directories;

    /**
     * @param string                $projectRootDirectory   /
     * @param string                $assetStorageDirectory  /var/assets
     * @param string                $publicRootDirectory    /public
     * @param string                $publicAssetsDirectory  /public/assets
     * @param CacheInterface        $cache
     * @param ManifestCache         $manifest
     * @param null|LoggerInterface  $logger
     */
    public function __construct(
        public readonly CacheInterface    $cache,
        private readonly ?LoggerInterface $logger = null,
    ) {
        $this->instantiationCheck();

        Asset::setManager( $this );
        AssetManager::$instance = $this;
    }

    public function getDirectory( string $key ) : string {
        return $this->directories[ $key ] ?? throw new \InvalidArgumentException(
            'The directory "' . $key . '" does not exist.',
        );
    }

    public function setDirectory( string $key, string $path ) : self {
        $this->directories[ $key ] = $path;
        return $this;
    }

    public static function get() : AssetManager {
        return AssetManager::getInstance();
    }

    public function getScript(
        string $src,
        array  $attributes = [],
        bool   $inline = false,
    ) : Script {
        return new Script( $src, $attributes, $inline );
    }

    public function getStylesheet(
        string $href,
        array  $attributes = [],
        bool   $inline = false,
    ) : Stylesheet {
        return new Stylesheet( $href, $attributes, $inline );
    }
}