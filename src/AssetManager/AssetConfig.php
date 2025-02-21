<?php

declare(strict_types=1);

namespace Core\AssetManager;

use Core\AssetManager\Config\{AssetReference, AssetRegistrationConfig};
use Core\AssetManager\Exception\MissingReferenceException;
use Core\Pathfinder;
use InvalidArgumentException;

// #[Lazy]
class AssetConfig
{
    public readonly AssetRegistrationConfig $register;

    /** @var string[] */
    public readonly array $assetDirectories;

    /** @var array<string, AssetReference> */
    private array $resolved;

    /**
     * @param Pathfinder      $pathfinder
     * @param string|string[] $assetDirectories
     * @param string|string[] $configFiles
     * @param string          $cacheDirectory        `dir.var/assets`
     * @param string          $publicAssetsDirectory `dir.public/assets`
     */
    final public function __construct(
        protected Pathfinder   $pathfinder,
        string|array           $assetDirectories,
        string|array           $configFiles,
        public readonly string $cacheDirectory = 'dir.var/assets',
        public readonly string $publicAssetsDirectory = 'dir.public/assets',
    ) {
        $this->register = new AssetRegistrationConfig( $this, $pathfinder );
        $this->assetDirectories( (array) $assetDirectories );
        $this->parseConfiguration( (array) $configFiles );
    }

    final public function getReference( string $reference ) : AssetReference
    {
        return $this->resolve()[$reference] ?? throw new MissingReferenceException(
            $reference,
            \array_keys( $this->resolve() ),
        );
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

    /**
     * @param string[] $assetDirectories
     *
     * @return void
     */
    private function assetDirectories( array $assetDirectories ) : void
    {
        foreach ( $assetDirectories as $assetDirectory ) {
            if ( ! $this->pathfinder->get( $assetDirectory ) ) {
                $message = "AssetConfig: directory '{$assetDirectory}' does not exist.";
                throw new InvalidArgumentException( $message );
            }
        }



        $this->assetDirectories = $assetDirectories;
    }

    /**
     * @param string[] $configFile
     *
     * @return void
     */
    private function parseConfiguration( array $configFile ) : void
    {
        foreach ( $configFile as $path ) {
            $config = $this->pathfinder->get( $path );

            if ( \file_exists( $config ) ) {
                ( require $config )( $this );
            }
        }
    }
}
