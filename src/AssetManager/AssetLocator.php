<?php

declare(strict_types=1);

namespace Core\AssetManager;

use Cache\LocalStorage;
use Core\AssetManager\Interface\AssetManagerInterface;
use Core\Asset\{Image, Type};
use Core\Pathfinder\Path;
use Core\Interface\{PathfinderInterface, StorageInterface};
use Psr\Cache\InvalidArgumentException;
use Psr\Log\LoggerInterface;
use Support\PhpStormMeta;

final readonly class AssetLocator
{
    public StorageInterface $manifest;

    /**
     * @param array<string, string[]> $registeredAssets
     * @param string                  $storageDirectory
     * @param PathfinderInterface     $pathfinder
     * @param null|LoggerInterface    $logger
     */
    public function __construct(
        private array               $registeredAssets,
        string                      $storageDirectory,
        private PathfinderInterface $pathfinder,
        private ?LoggerInterface    $logger = null,
    ) {
        $this->manifest = new LocalStorage(
            $this->pathfinder->get( "{$storageDirectory}/asset_manifest.php" ),
        );
    }

    public function getReference( string $reference ) : AssetReference
    {
        try {
            $data = $this->manifest->get( $reference, fn() => $this->discoverAll() );
        }
        catch ( InvalidArgumentException $e ) {
            throw new \InvalidArgumentException( $e->getMessage() );
        }
        return new AssetReference(
            $reference,
            $data,
        );
    }

    /**
     * @param ?string                                                           $projectDirectory
     * @param array{0: class-string, 1: string}|callable|callable-string|string ...$functionReference
     *
     * @return void
     */
    final public function updatePhpStormMeta(
        ?string                  $projectDirectory,
        array|string|callable ...$functionReference,
    ) : void {
        $meta = new PhpStormMeta( $projectDirectory );

        $meta->registerArgumentsSet(
            'asset_reference_keys',
            ...$this->manifest->getKeys(),
        );

        $generateReferences = \array_merge(
            [
                [AssetManagerInterface::class, 'getAsset'],
                [AssetManagerInterface::class, 'getReference'],
                [AssetManagerInterface::class, 'getReference'],
            ],
            $functionReference,
        );

        foreach ( $generateReferences as $generateReference ) {
            $meta->expectedArguments( $generateReference, [0 => 'asset_reference_keys'] );
        }

        $meta->save( 'asset_manifest' );
    }

    public function discoverImages() : void
    {
        $registered = $this->registeredAssets['images'] ?? [];

        foreach ( $registered as $directory ) {
            $path = $this->pathfinder->getPath( $directory );
            $this->scanImageDirectory(
                $path,
                $path->getRealPath(),
            );
        }
    }

    public function discoverAll() : void
    {
        foreach ( $this->registeredAssets as $reference => $asset ) {
            match ( Type::from( $reference ) ) {
                Type::IMAGE  => $this->discoverImages(),
                Type::STYLE  => $this->discoverStyle( $asset, $reference ),
                Type::SCRIPT => $this->discoverScripts( $asset, $reference ),
                default      => null,
            };
        }
    }

    private function discoverStyle( array $source, string $reference ) : void
    {
        $styles = [];

        foreach ( $source as $path ) {
            if ( \str_contains( $path, '*' ) ) {
                [$dir, $glob] = \explode( '*', $path, 2 );

                $scan = ( new Path( $dir ) )->glob( "*{$glob}" );

                foreach ( $scan as $file ) {
                    $styles[] = $file->getRealPath();
                }
            }
            else {
                $styles[] = $path;
            }
        }

        $this->manifest->set(
            $reference,
            $styles,
        );
    }

    private function discoverScripts( array $source, string $reference ) : void
    {
        $this->manifest->set(
            $reference,
            $source,
        );
    }

    private function scanImageDirectory( Path $directory, string $relativeTo ) : void
    {
        foreach ( $directory->glob( '*' ) as $path ) {
            // Determine Type
            $type = Type::from( $path->getExtension() );

            // Scan nested directories
            if ( $path->isDirectory() ) {
                $this->scanImageDirectory( $path, $relativeTo );

                continue;
            }

            if ( $type !== Type::IMAGE ) {
                $this->logger?->error(
                    'Unexpected file extension {ext} when {class} images.',
                    [
                        'class' => $this::class,
                        'ext'   => $path->getExtension(),
                    ],
                );

                continue;
            }

            $string = $this->pathfinder->get( $path, $relativeTo );

            $this->manifest->set(
                Image::key( $string ),
                $path->getRealPath(),
            );
        }
    }
}
