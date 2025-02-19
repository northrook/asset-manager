<?php

declare(strict_types=1);

namespace Core\AssetManager;

use Core\AssetManager\Config\{AssetReference, AssetRegistrationConfig};
use Core\Pathfinder;
use Symfony\Component\DependencyInjection\Attribute\Lazy;

#[Lazy]
class AssetConfig
{
    public readonly AssetRegistrationConfig $register;

    /** @var array<string, AssetReference> */
    private array $resolved;

    /**
     * @param Pathfinder      $pathfinder
     * @param string[]        $assetDirectories
     * @param string|string[] $configFiles
     * @param string          $cacheDirectory        `dir.var/assets`
     * @param string          $publicAssetsDirectory `dir.public/assets`
     */
    final public function __construct(
        Pathfinder             $pathfinder,
        public readonly array  $assetDirectories,
        string|array           $configFiles,
        public readonly string $cacheDirectory = 'dir.var/assets',
        public readonly string $publicAssetsDirectory = 'dir.public/assets',
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
