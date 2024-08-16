<?php

namespace Northrook\AssetManager;

use Northrook\AssetManager;
use Northrook\Core\Exception\MissingPropertyException;
use Northrook\Logger\Log;
use Northrook\Support\File;
use Psr\Cache\InvalidArgumentException;
use Symfony\Component\Cache\CacheItem;
use Symfony\Component\Cache\Exception\CacheException;
use Symfony\Contracts\Cache\CacheInterface;
use function Northrook\Core\Function\hashKey;
use function Northrook\Core\Function\normalizeKey;

abstract class Asset implements \Stringable
{
    private static AssetManager $assetManager;

    protected string $assetID;
    protected string $assetType;
    protected string $html;

    final protected function manager() : AssetManager {
        return $this::$assetManager ?? throw new MissingPropertyException(
            'assetManager', $this::class,
            $this::class . ' tried calling required AssetManager. Use the static function ' . self::class . '::setManager() to do so.',
        );
    }


    final protected function cache() : CacheInterface {
        return $this->manager()->cache;
    }

    final public function clearCache() : self {
        try {
            $this->cache()->delete( $this->assetID );
        }
        catch ( InvalidArgumentException $e ) {
            Log::exception( $e );
        }
        return $this;
    }

    final protected function cachedAsset() : string {
        try {
            return $this->cache()->get(
                $this->assetID, function ( CacheItem $cache ) : string {
                $cache->expiresAfter( 1 );

                return $this->build();
            },
            );
        }
        catch ( InvalidArgumentException $e ) {
            Log::exception( $e );
            return '';
        }
    }

    final public static function setManager( AssetManager $assetManager ) : void {
        self::$assetManager ??= $assetManager;
    }


    /**
     * Build the asset. Must return valid HTML.
     *
     * @return string
     */
    abstract protected function build() : string;

    /**
     * Return the {@see $html}, calls {@see build()} if necessary.
     *
     * @return string
     */
    public function __toString() : string {
        return $this->html ??= $this->build();
    }

    public static function generateFilenameKey( string $path ) : string {
        $trimmed = \preg_replace( '/^(?:\w*:\/\/)*(.*?)(\?.*)?$/m', '$1', $path );
        return normalizeKey( $trimmed ?? $path );
    }

}