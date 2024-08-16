<?php

declare( strict_types = 1 );

namespace Northrook\AssetManager\Asset;

use Northrook\Asset\Script;
use Northrook\Asset\Stylesheet;
use Northrook\AssetManager\Compiler\Trait\AssetManagerTrait;
use Northrook\Logger\Log;
use Northrook\Support\File;
use Psr\Cache\InvalidArgumentException;
use Symfony\Component\Cache\CacheItem;
use function Northrook\Core\Function\classBasename;
use function Northrook\Core\Function\hashKey;
use function Northrook\Core\Function\normalizeKey;
use function Northrook\Core\Function\normalizePath;

/**
 * - {@see Stylesheet}
 * - {@see Script}
 *
 * @internal
 */
abstract class StaticAsset implements AssetInterface, \Stringable
{
    use AssetManagerTrait;

    protected const TYPE = null;

    protected readonly File $publicFile;
    protected array         $attributes = [];
    protected string        $html;

    public readonly string $assetID;
    public readonly string $assetType;


    /**
     * Build the asset. Must return valid HTML.
     *
     * @return string
     */
    abstract protected function build() : string;

    /**
     * Attempt to retrieve a precompiled asset from the cache,
     * else {@see build()} the asset, store, and return it.
     *
     * @return void
     */
    final protected function compileStaticAsset() : void {
        try {
            $this->html = $this->cache()->get(
                $this->assetID,
                function ( CacheItem $cache ) : string {
                    $cache->expiresAfter( 1 );

                    return $this->build();
                },
            );
        }
        catch ( InvalidArgumentException $e ) {
            Log::exception( $e );
        }
    }

    // BUILD - Only run when compiling  -------------

    final protected function createPublicAssetFile( string $source, ?string $directory = null ) : void {

        $composerVendorDirectory = normalizePath(
            "{$this->manager()->projectRootDirectory}/vendor",
        );

    }


    // END - BUILD -------------

    public function getAssetID() : string {
        return $this->assetID;
    }

    public function getHtml() : string {
        return $this->html ??= $this->build();
    }

    public function __toString() : string {
        return $this->getHtml();
    }

    public function recompileAsset() : AssetInterface {
        try {
            $this->cache()->delete( $this->assetID );
            $this->compileStaticAsset();
        }
        catch ( InvalidArgumentException $e ) {
            Log::exception( $e );
        }
        return $this;
    }


    /**
     * Generate a key based on the {@see $path}.
     *
     * - Strips URI schema and parameters
     *
     * ```
     *        |      matched     |
     * https://unpkg.com/htmx.org?v=1720704985
     *
     * ```
     *
     * @param string  $path
     *
     * @return string
     */
    public static function generateFilenameKey( string $path ) : string {
        $trimmed = \preg_replace( '/^(?:\w*:\/\/)*(.*?)(\?.*)?$/m', '$1', $path );
        return normalizeKey( $trimmed ?? $path );
    }
}