<?php

namespace Core\Service\AssetManager;

use Core\Service\AssetManager\Asset\AssetModelInterface;
use Core\Service\AssetManager\Interface\AssetManifestInterface;
use Northrook\ArrayStore;
use Psr\Log\LoggerInterface;

/**
 * @template TKey of array-key
 * @template TValue as mixed|array<TKey,TValue>
 *
 * @extends ArrayStore<TKey,TValue>
 */
final class AssetManifest extends ArrayStore implements AssetManifestInterface
{
    public function __construct(
        string           $storagePath,
        ?LoggerInterface $logger = null,
    ) {
        parent::__construct( $storagePath, $this::class, false, true, $logger );
    }

    public function registerAsset( AssetModelInterface $asset ) : void
    {
        // TODO: Implement registerAsset() method.
    }

    public function hasAsset( string $name ) : bool
    {
        return false;
    }

    public function getRegisteredAssets() : array
    {
        dump(
            [
                __METHOD__,
                [AssetModelInterface::class],
            ],
        );

        return [];
    }

    public function getAssetBlueprint( string $asset ) : ?AssetModelInterface
    {
        dump(
            [
                __METHOD__,
                AssetModelInterface::class,
            ],
        );
        return null;
    }
}
