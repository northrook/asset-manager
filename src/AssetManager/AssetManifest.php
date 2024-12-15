<?php

namespace Core\Service\AssetManager;

use Core\Service\AssetManager\Asset\AssetReference;
use Core\Service\AssetManager\Exception\UndefinedAssetReferenceException;
use Northrook\ArrayStore;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;

#[Autoconfigure(
    lazy     : true,   // lazy-load using ghost
    public   : false,  // private
    autowire : false,  // manual injection only
)]
class AssetManifest
{
    /** @var ArrayStore<string, array> */
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

    final public function has( string $asset ) : bool
    {
        return $this->manifest->has( $asset );
    }

    /**
     * @param string $asset
     *
     * @return ?AssetReference
     */
    final public function get( string $asset ) : ?AssetReference
    {
        $reference = $this->manifest->get( $asset );

        if ( ! $reference ) {
            throw new UndefinedAssetReferenceException( $asset, \array_keys( $this->manifest->flatten() ) );
        }

        return AssetReference::hydrate( ...$reference );
    }

    final public function register( AssetReference $reference ) : self
    {
        $this->manifest->set( $reference->name, $reference->toArray() );
        return $this;
    }
}
