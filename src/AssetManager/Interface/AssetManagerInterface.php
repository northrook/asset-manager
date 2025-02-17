<?php

declare(strict_types=1);

namespace Core\AssetManager\Interface;

use Core\AssetManager\Config\AssetReference;

/**
 * @author Martin Nielsen <mn@northrook.com>
 */
interface AssetManagerInterface
{
    /**
     * @param AssetReference|string                     $reference
     * @param ?string                                   $assetID
     * @param array<string, null|bool|float|int|string> $attributes
     *
     * @return null|AssetInterface
     */
    public function getAsset(
        string|AssetReference $reference,
        ?string               $assetID = null,
        array                 $attributes = [],
    ) : ?AssetInterface;

    // /**
    //  * @param string $asset
    //  *
    //  * @return AssetReference
    //  */
    // public function getReference( string $asset ) : AssetReference;
}
