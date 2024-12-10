<?php

declare(strict_types=1);

namespace Core\Service\AssetManager\Interface;

use Core\Service\AssetManager\Asset\AssetModelInterface;

interface AssetManifestInterface
{
    public function registerAsset( AssetModelInterface $asset ) : void;

    public function hasAsset( string $name ) : bool;

    /**
     * Returns only manually registered assets.
     *
     * @return \Core\Service\AssetManager\Interface\AssetModelInterface[]
     */
    public function getRegisteredAssets() : array;

    /**
     * Return an {@see AssetModelInterface} if registered.
     *
     * @param string $asset
     *
     * @return ?AssetModelInterface
     */
    public function getAssetBlueprint( string $asset ) : ?AssetModelInterface;
}
