<?php

namespace Core\Service\AssetManager;

use JetBrains\PhpStorm\Deprecated;
use Core\{PathfinderInterface,
    Service\AssetManager,
    Service\AssetManager\AssetManifest\AssetModel,
    Service\AssetManager\Asset\Type,
    Service\AssetManager\Interface\AssetModelInterface,
    Service\AssetManager\Model\ScriptAsset,
    Service\AssetManager\Model\StyleAsset
};
use Psr\Log\LoggerInterface;
use Support\Normalize;
use Symfony\Component\Filesystem\Filesystem;
use const Support\AUTO;
use InvalidArgumentException;

#[Deprecated]
class AssetCompiler
{
    private static ?Filesystem $filesystem;

    /** @var array<string, string> */
    private array $assetDirectories = [
        'root'     => AssetManager::DIR_ASSETS,
        'style'    => AssetManager::DIR_STYLES,
        'script'   => AssetManager::DIR_SCRIPTS,
        'font'     => AssetManager::DIR_FONTS,
        'image'    => AssetManager::DIR_IMAGES,
        'video'    => AssetManager::DIR_VIDEOS,
        'document' => AssetManager::DIR_DOCUMENTS,
    ];

    /** @var string appends the {@see self::publicAssetPath}. */
    private string $defaultDirectory = 'assets';

    /** @var array */
    private array $foundAssets = [];

    /**
     * @param PathfinderInterface $pathfinder
     * @param AssetManifest       $manifest
     * @param ?LoggerInterface    $logger
     */
    public function __construct(
        private readonly PathfinderInterface $pathfinder,
        private readonly AssetManifest       $manifest,
        private readonly ?LoggerInterface    $logger,
    ) {
        // Reset the Filesystem on initialization
        AssetCompiler::$filesystem = null;
    }

    /**
     * Retrieve one or more root asset directories by {@see Type}
     *
     * @param 'document'|'font'|'image'|'root'|'script'|'style'|'video'|Type ...$scan
     *
     * @return self
     */
    final public function scanDirectories( string|Type ...$scan ) : self
    {
        foreach ( $this->getAssetDirectories( ...$scan ) as $key => $directory ) {
            $type = Type::from( $key ) ?? null;
            $path = $this->pathfinder->get( $directory );

            if ( ! $path ) {
                $this->logger?->error( 'No path found for '.$directory );

                continue;
            }

            if ( ! \file_exists( $path ) ) {
                $this->filesystem()->mkdir( $path );
            }

            $scan = new Compiler\AssetScanner( $path, $type, $this->logger );

            if ( $scan->hasResults() ) {
                $this->foundAssets[$key] = $scan->getResults();
            }
        }

        return $this;
    }

    /*
    :: Loop through each provided by [scanDirectory]

    Create an AssetModel from each
    The AssetModel extends ArrayStore
    If it exists, only update if any provided is newer

    Each AssetModel gets added as [$name => $hash] to AssetManifest

    When AssetManager requests an asset, it first checks internal cache for $hash.
    If no $hash, it calls the AssetFactory which checks the Manifest by $name.
    The Factory will invoke the relevant AssetModel if found, otherwise throw exception.

    AssetModel knows who it is, where to get its source files, and where to store it.

    The Asset is then bundled, optimized, generated - based on the Model type.

    If successful, the Factory returns an AssetInterface, with fully resolved HTML.

     */

    final public function compileAssets( Compiler\ScannedAssetReference ...$reference ) : self
    {
        if ( ! $reference ) {
            foreach ( $this->foundAssets as $prefix => $foundReferences ) {
                foreach ( $foundReferences as $referencePrefix => $foundReference ) {
                    $reference["{$prefix}.{$referencePrefix}"] = $foundReference;
                }
            }
        }

        foreach ( $reference as $key => $asset ) {
            $asset = $this->compile( $asset );
            // // $compile = match ( $type ) {
            // //     Type::STYLE    => $this->discoverStyleAssets( $path ),
            // //     Type::SCRIPT   => $this->discoverScriptAssets( $path ),
            // //     Type::FONT     => $this->discoverFontAssets( $path ),
            // //     Type::IMAGE    => $this->discoverImageAssets( $path ),
            // //     Type::VIDEO    => $this->discoverVideoAssets( $path ),
            // //     Type::DOCUMENT => $this->discoverDocumentAssets( $path ),
            // //     default        => $this->discoverAssets( $path ),
            // // };
            dump( $asset );
        }
        return $this;
    }

    final public function compile( Compiler\ScannedAssetReference $assetReference ) : ?AssetModelInterface
    {
        // Takes an AssetInfo, generates an AssetModel, stores AssetReference in AssetManifest

        $model = match ( $assetReference->type ) {
            Type::STYLE  => StyleAsset::fromReference( $assetReference ),
            Type::SCRIPT => ScriptAsset::fromReference( $assetReference ),
            // Type::FONT     => $this->discoverFontAssets( $path ),
            // Type::IMAGE    => $this->discoverImageAssets( $path ),
            // Type::VIDEO    => $this->discoverVideoAssets( $path ),
            // Type::DOCUMENT => $this->discoverDocumentAssets( $path ),
            default => null,
        };

        if ( ! $model ) {
            $this->logger?->error( "Unknown asset reference: {$assetReference->type->name}" );
            return null;
        }

        $this->manifest->register(
            $model->getName(),
            $model->getReference(),
        );

        return $model;
    }

    final public function publicAssetPath(
        string            $path,
        null|string|false $directory = AUTO,
        bool              $relative = false,
    ) : ?string {
        if ( false !== $directory ) {
            $directory ??= $this->defaultDirectory;
            $path = $directory.DIRECTORY_SEPARATOR.$path;
        }

        $path       = AssetManager::DIR_PUBLIC_KEY.DIRECTORY_SEPARATOR.$path;
        $relativeTo = $relative ? AssetManager::DIR_PUBLIC_KEY : null;

        return $this->pathfinder->get( $path, $relativeTo );
    }

    final public function publicAssetsUrl(
        string            $path,
        null|string|false $directory = AUTO,
        bool              $absolute = false,
    ) : string {
        $pathFromPublic = $this->publicAssetPath( $path, $directory, true );

        if ( ! $pathFromPublic ) {
            $message = __METHOD__.': resolved path is empty.';
            throw new InvalidArgumentException( $message );
        }

        return Normalize::url( $pathFromPublic );
    }

    /**
     * Ensure all {@see AssetCompiler::$assetDirectories} exist and are valid.
     *
     * Usually run during a {@see CompilerPass}.
     *
     * @param bool $returnException
     *
     * @return InvalidArgumentException|true
     *
     * @throws InvalidArgumentException if a directory is `invalid` or a `file`
     */
    final public function prepareAssetDirectories( bool $returnException = false ) : true|InvalidArgumentException
    {
        // Inverted validation - assume everything will be OK
        $result = true;

        foreach ( $this->assetDirectories as $key => $directory ) {
            $path = $this->pathfinder->getFileInfo( $directory );

            if ( ! $path ) {
                $result = new InvalidArgumentException( 'Invalid asset directory: '.$directory );

                break;
            }

            // Ensure the directory exists
            if ( $path->exists() ) {
                $this->filesystem()->mkdir( $path );
            }

            // Ensure the set $path is a readable directory.
            if ( $path->isFile() || ! $path->isReadable() ) {
                $result = new InvalidArgumentException();

                break;
            }
        }

        // Throw Exceptions by default
        if ( $result instanceof InvalidArgumentException && false === $returnException ) {
            throw $result;
        }

        return $result;
    }

    /**
     * Retrieve one or more root asset directories by {@see Type}
     *
     * @param 'document'|'font'|'image'|'root'|'script'|'style'|'video'|Type ...$get
     *
     * @return array<string, string>
     */
    final protected function getAssetDirectories( string|Type ...$get ) : array
    {
        // Get by arguments
        if ( $get ) {
            $directories = [];

            foreach ( $get as $directory ) {
                $directory = \strtolower( $directory instanceof Type ? $directory->name : $directory );

                if ( ! \array_key_exists( $directory, $this->assetDirectories ) ) {
                    $message = __METHOD__.": Directory '{$directory}' is not a valid asset directory.";
                    throw new InvalidArgumentException( $message );
                }

                $directories[$directory] = $this->assetDirectories[$directory];
            }
            return $directories;
        }

        // Get all
        return $this->assetDirectories;
    }

    final protected function filesystem() : Filesystem
    {
        return AssetCompiler::$filesystem ??= new Filesystem();
    }
}
