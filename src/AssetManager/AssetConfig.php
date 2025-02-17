<?php

declare(strict_types=1);

namespace Core\AssetManager;

use Core\AssetManager\AssetConfig\AssetRegistrationConfig;
use Core\Interface\PathfinderInterface;
use Symfony\Component\DependencyInjection\Attribute\Lazy;

#[Lazy]
class AssetConfig
{
    public readonly AssetRegistrationConfig $register;

    /** @var array<string, AssetReference> */
    private array $resolved;

    /**
     * @param PathfinderInterface $pathfinder
     * @param string              $cacheDirectory
     * @param string[]            $assetDirectories
     * @param string|string[]     $configFiles
     */
    final public function __construct(
        PathfinderInterface    $pathfinder,
        public readonly string $cacheDirectory,
        public readonly array  $assetDirectories,
        string|array           $configFiles,
    ) {
        $this->register = new AssetRegistrationConfig( $this, $pathfinder );

        foreach ( (array) $configFiles as $configPath ) {
            $path = $pathfinder->getPath( $configPath );

            if ( $path->exists() ) {
                ( require $path->getRealPath() )( $this );
            }
        }
    }

    /**
     * @param bool $recompile
     *
     * @return array<string, AssetReference>
     */
    final public function resolve( bool $recompile = false ) : array
    {
        if ( isset( $this->resolved ) && ! $recompile ) {
            return $this->resolved;
        }

        $assets = [];

        foreach ( $this->register->assets as $asset ) {
            $reference = new AssetReference(
                $asset->name,
                $asset->getSource(),
                $asset->getServices(),
            );

            $assets[$asset->name] = $reference;
        }

        return $this->resolved = $assets;
    }
}
