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
        string                  $cacheDirectory = 'dir.var/assets',
        string                  $publicAssetsDirectory = 'dir.public/assets',
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
     * @return string URL relative to `public`
     */
    public function getSourceUrl() : string;
}
