<?php

namespace Core\AssetManager\Interface;

use Core\AssetManager\Config\AssetReference;
use Core\Interface\ViewInterface;
use Core\Pathfinder;
use Core\View\Element;
use Core\View\Element\Attributes;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Log\LoggerInterface;

/**
 * @property-read string $assetID
 */
interface AssetInterface extends ViewInterface
{
    public function setDependencies(
        AssetReference          $reference,
        Pathfinder              $pathfinder,
        ?CacheItemPoolInterface $cache = null,
        ?LoggerInterface        $logger = null,
        string                  $publicDirectory = 'dir.var/assets',
        string                  $publicAssetsDirectory = 'dir.public/assets',
    ) : self;

    public function getReference() : AssetReference;

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

    /**
     * Return the HTML element for this Asset.
     *
     * Called when using {@see self::getHtml()} or cast to `string`.
     *
     * @param array<string, null|bool|int|string>|Attributes $attributes
     *
     * @return Element
     */
    public function element( array|Attributes $attributes = [] ) : Element;

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
