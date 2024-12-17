<?php

declare(strict_types=1);

namespace Core\Service\AssetManager\Interface;

use Core\Service\AssetManager\Asset\AssetReference;

interface AssetManifestInterface
{
    public function has( string|AssetReference $asset ) : bool;

    /**
     * @param string $asset
     * @param bool   $nullable [false] throw by default
     *
     * @return ($nullable is true ? null|AssetReference : AssetReference)
     */
    public function get( string $asset, bool $nullable = false ) : ?AssetReference;

    public function register( AssetReference $reference ) : self;
}
