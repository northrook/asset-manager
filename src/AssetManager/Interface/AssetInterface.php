<?php

namespace Core\AssetManager\Interface;

use Core\Asset\Type;
use Core\Pathfinder;
use Core\View\Element;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Log\LoggerInterface;
use Stringable;

/**
 * @property-read string $name
 * @property-read Type   $type
 * @property-read string $assetID
 */
interface AssetInterface extends Stringable
{
    /**
     * Set by the {@see AssetManifest}.
     *
     * @internal
     *
     * @param Pathfinder $pathfinder
     *
     * @param ?CacheItemPoolInterface $cache
     * @param ?LoggerInterface        $logger
     *
     * @return self
     */
    public function setDependencies(
        Pathfinder              $pathfinder,
        ?CacheItemPoolInterface $cache = null,
        ?LoggerInterface        $logger = null,
    ) : self;

    /**
     * Parses and compiles all provided sources.
     *
     * Called by the {@see \Core\AssetManager}.
     *
     * @param null|string $assetID
     *
     * @return self
     */
    public function build( ?string $assetID = null ) : self;

    public function getHtml() : string;

    /**
     * Return the HTML element for this Asset.
     *
     * Called when using {@see self::getHtml()} or cast to `string`.
     *
     * @param mixed ...$attributes
     *
     * @return Element
     */
    public function getElement( mixed ...$attributes ) : Element;

    /**
     * @return string
     */
    public function getSourcePath() : string;

    /**
     * @param bool $version Append `?v=`{@see self::getVersion()}
     *
     * @return string URL relative to `public`
     */
    public function getSourceUrl( bool $version = false ) : string;

    /**
     * Get a version string for this Asset.
     *
     * Provides the {@see self::$assetID} by default.
     *
     * @return string
     */
    public function getVersion() : string;
}
