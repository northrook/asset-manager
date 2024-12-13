<?php

namespace Core\Service\AssetManager;

use Core\Service\AssetManager\AssetManifest\AssetReference;
use Northrook\{ArrayStore};
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;

#[Autoconfigure(
    lazy     : true,   // lazy-load using ghost
    public   : false,  // private
    autowire : false,  // manual injection only
)]
class AssetManifest
{
    /** @var ArrayStore<string, AssetReference> */
    private readonly ArrayStore $manifest;

    final public function __construct(
        string           $storagePath,
        ?LoggerInterface $logger = null,
    ) {
        $this->manifest = new ArrayStore(
            $storagePath,
            $this::class,
            false,
            false, // ::[DEBUG]
            $logger,
        );
    }

    final public function has( string $asset ) : bool
    {
        return $this->manifest->has( $asset );
    }

    /**
     * @param string $asset
     *
     * @return null|array<string, AssetReference>|AssetReference
     */
    final public function get( string $asset ) : mixed
    {
        return $this->manifest->get( $asset );
    }

    final public function register( string $asset, AssetReference $reference ) : self
    {
        $this->manifest->set( $asset, $reference );
        return $this;
    }
}
