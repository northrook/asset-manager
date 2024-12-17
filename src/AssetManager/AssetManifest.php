<?php

namespace Core\Service\AssetManager;

use Core\Service\AssetManager\Asset\AssetReference;
use Core\Service\AssetManager\Exception\UndefinedAssetReferenceException;
use Core\Service\AssetManager\Interface\{AssetManagerInterface, AssetManifestInterface};
use Northrook\ArrayStore;
use Psr\Log\LoggerInterface;
use Support\PHPStorm;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;

#[Autoconfigure(
    lazy     : true,   // lazy-load using ghost
    public   : false,  // private
    autowire : false,  // manual injection only
)]
class AssetManifest implements AssetManifestInterface
{
    /** @var ArrayStore<string, array{name: string, publicUrl: string, source: string|string[], type: string, properties: null|string}> */
    private readonly ArrayStore $manifest;

    final public function __construct(
        string           $storagePath,
        ?LoggerInterface $logger = null,
    ) {
        $this->manifest = new ArrayStore(
            $storagePath,
            $this::class,
            false,
            true, // ::[DEBUG]
            $logger,
        );
        if ( ! \file_exists( $storagePath ) ) {
            $this->manifest->save();
        }
    }

    final public function has( string|AssetReference $asset ) : bool
    {
        if ( $asset instanceof AssetReference ) {
            $asset = $asset->name;
        }
        return $this->manifest->has( $asset );
    }

    /**
     * @param string $asset
     * @param bool   $nullable [false] throw by default
     *
     * @return ($nullable is true ? null|AssetReference : AssetReference)
     */
    final public function get( string $asset, bool $nullable = false ) : ?AssetReference
    {
        $reference = $this->manifest->get( $asset );

        if ( ! $reference ) {
            if ( $nullable ) {
                return null;
            }
            throw new UndefinedAssetReferenceException( $asset, \array_keys( $this->manifest->flatten() ) );
        }

        return \unserialize( $reference );
        // return AssetReference::hydrate( ...$reference );
    }

    /**
     * @param AssetReference $reference
     *
     * @return $this
     */
    final public function register( AssetReference $reference ) : self
    {
        // $this->manifest->set( $reference->name, $reference->toArray() );
        $this->manifest->set( $reference->name, \serialize( $reference ) );
        return $this;
    }

    final public function updateEditorMeta( string $projectDirectory ) : void
    {
        $manifestKeys = \array_keys( $this->manifest->flatten() );

        PHPStorm::generateMeta( $projectDirectory )->stringValues(
            'asset_manifest',
            [AssetManifestInterface::class, 'get'],
            0,
            ...$manifestKeys,
        );
        PHPStorm::generateMeta( $projectDirectory )->stringValues(
            'asset_manager',
            [AssetManagerInterface::class, 'get'],
            0,
            ...$manifestKeys,
        );
    }
}
