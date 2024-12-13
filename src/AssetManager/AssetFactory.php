<?php

declare(strict_types=1);

namespace Core\Service\AssetManager;

use Core\Service\AssetManager\AssetManifest\{AssetModelInterface};
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;

/**
 * ### Public Assets Directory
 * `./app/public/assets/..`
 *
 * ### Assets Build Directory
 * `./app/var/assets/..`
 */
#[Autoconfigure( lazy : true )]
final class AssetFactory
{
    /**
     * @param string $publicAssetsDirectory `./app/public/assets/..`
     * @param string $assetsBuildDirectory  `./app/var/assets/..`
     * @param array  $registeredAssets
     */
    public function __construct(
        protected readonly string $publicAssetsDirectory,
        protected readonly string $assetsBuildDirectory,
        private readonly array    $registeredAssets,
        // private readonly ?AssetManifest $assetManifest = null,
    ) {
        dump( $this );
    }

    protected function getAssetModel( string $name ) : AssetModelInterface
    {
        // return new
    }
}
