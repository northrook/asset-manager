<?php

declare(strict_types=1);

namespace Core\Service\AssetManager;

final class AssetFactory
{
    public function __construct(
        private readonly array $registeredAssets,
        // private readonly ?AssetManifest $assetManifest = null,
    ) {
        dump( $this );
    }
}
