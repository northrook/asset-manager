<?php

declare(strict_types=1);

namespace Core\AssetManager\Interface;

use Core\AssetManager\AssetReference;

/**
 * @author Martin Nielsen <mn@northrook.com>
 */
interface AssetManagerInterface
{
    /**
     * @param AssetReference|string                     $asset
     * @param ?string                                   $assetID
     * @param array<string, null|bool|float|int|string> $attributes
     *
     * @return null|AssetInterface
     */
    public function getAsset(
        string|AssetReference $asset,
        ?string               $assetID = null,
        array                 $attributes = [],
    ) : ?AssetInterface;

    /**
     * @param string $asset
     *
     * @return null|AssetReference
     */
    public function getReference( string $asset ) : ?AssetReference;
}
