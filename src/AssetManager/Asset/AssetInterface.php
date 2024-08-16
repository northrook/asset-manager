<?php

declare( strict_types = 1 );

namespace Northrook\AssetManager\Asset;

use Stringable;

interface AssetInterface
{
    public function isValid() : bool;

    /**
     * Retrieve the ID for this {@see AssetInterface}.
     *
     * The ID:
     * - May be auto-generated or manually supplied.
     * - Will be unique to this asset.
     * - Can be used to retrieve the Asset.
     *
     * @return string
     */
    public function getAssetID() : string;

    /**
     * Retrieve trusted HTML ready for public use.
     *
     * @return string
     */
    public function getHtml() : string;

    /**
     * - Clear the {@see self::class} from the cache.
     * - Recompiles the asset HTML.
     * - Cache recompiled HTML.
     *
     * @return self
     */
    public function recompileAsset() : self;
}