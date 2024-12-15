<?php

namespace Core\Service\AssetManager;

use Core\PathfinderInterface;
use Core\Service\AssetManager;
use Core\Service\AssetManager\Asset\{AssetReference, Type};
use Core\Service\AssetManager\Exception\InvalidAssetTypeException;
use InvalidArgumentException;
use JetBrains\PhpStorm\Deprecated;
use Northrook\Filesystem\File;
use Psr\Log\LoggerInterface;
use Support\{FileInfo, Filesystem, Interface\ActionInterface, Normalize, Str};
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use Symfony\Component\Finder\Finder;
use const Support\AUTO;
use SplFileInfo;

#[Autoconfigure( lazy : true, public : false )]
class AssetLocator implements ActionInterface
{
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
    private array $located = [];

    /**
     * @param PathfinderInterface $pathfinder
     * @param AssetManifest       $manifest
     * @param ?LoggerInterface    $logger
     */
    final public function __construct(
        private readonly PathfinderInterface $pathfinder,
        private readonly AssetManifest       $manifest,
        private readonly ?LoggerInterface    $logger,
    ) {
    }

    /**
     * Locates and returns a `registered` {@see AssetReference}.
     *
     * @param string $get
     *
     * @return AssetReference
     */
    public function __invoke( string $get ) : AssetReference
    {
        return $this->manifest->get( $get );
    }

    /**
     * Intended to be blindly called to discover and register all assets.
     *
     * - Scan all {@see self::assetDirectories} by default.
     * - Each {@see AssetReference} is added or updated in the {@see self::$manifest}.
     *
     * @param 'document'|'font'|'image'|'root'|'script'|'style'|'video'|Type ...$scan
     *
     * @return $this
     */
    final public function discover( string|Type ...$scan ) : self
    {
        foreach ( $this->scan( ...$scan ) as $reference ) {
            dump( $reference->toArray() );
            $this->manifest->register( $reference );
        }
        return $this;
    }

    /**
     * Retrieve one or more root asset directories by {@see Type}
     *
     * @param 'document'|'font'|'image'|'root'|'script'|'style'|'video'|Type ...$scan
     *
     * @return AssetReference[]
     */
    final public function scan( string|Type ...$scan ) : array
    {
        $found = [];

        foreach ( $this->getAssetDirectories( ...$scan ) as $key => $directory ) {
            $type = Type::from( $key ) ?? null;

            // Skip `./assets/~` for now
            if ( ! $type ) {
                continue;
            }

            if ( ! $directory ) {
                $this->logger?->error( 'No path found for '.$directory );

                continue;
            }

            if ( ! $directory->exists() ) {
                $directory->mkdir();
            }

            // dump( [$key => $type?->name] );

            $scannedAssetFiles = match ( $type ) {
                Type::STYLE, Type::SCRIPT => $this->scanDocumentAssets( $directory, $type ),
                Type::IMAGE => $this->scanImageAssets( $directory, $type ),
                default     => [],
            };

            $found = \array_merge( $found, $scannedAssetFiles );
        }

        return $found;
    }

    /**
     * @internal
     *
     * @param FileInfo $directory
     * @param Type     $type
     *
     * @return AssetReference[]
     */
    final protected function scanImageAssets( FileInfo $directory, Type $type ) : array
    {
        $results = [];

        $finder = new Finder();

        $finder->files()->in( $directory );

        if ( $finder->hasResults() ) {
            foreach ( $finder as $splFileInfo ) {
                $ext = $splFileInfo->getExtension();

                if ( ! $ext || ! Type::from( $ext ) ) {
                    $this->logger?->error( 'Invalid asset type when scanning images: '.$splFileInfo->getExtension() );

                    continue;
                }

                $reference = AssetReference::create(
                    $this->assetName( $splFileInfo, $type ),
                    $type,
                    $this->relativeAssetPath( $splFileInfo ),
                    $this->relativePublicAsset( $splFileInfo, $ext ),
                );
                $results[$reference->name] = $reference;
            }
        }

        return $results;
    }

    /**
     * @internal
     *
     * @param FileInfo $directory
     * @param Type     $type
     *
     * @return AssetReference[]
     */
    final protected function scanDocumentAssets( FileInfo $directory, Type $type ) : array
    {
        $ext = match ( $type ) {
            Type::STYLE  => 'css',
            Type::SCRIPT => 'js',
            default      => throw new InvalidAssetTypeException( $type ),
        };

        $results = [];

        /** @var FileInfo[] $parse */
        $parse = [
            ...$directory->glob( "/*.{$ext}", asFileInfo : true ),
            ...$directory->glob( '/**/', asFileInfo : true ),
        ];

        foreach ( $parse as $fileInfo ) {
            $reference = AssetReference::create(
                $this->assetName( $fileInfo, $type ),
                $type,
                $this->relativeAssetPath( $fileInfo, $ext ),
                $this->relativePublicAsset( $fileInfo, $ext ),
            );
            $results[$reference->name] = $reference;
        }

        return $results;
    }

    private function assetName( string|SplFileInfo $from, Type $type ) : string
    {
        $assetType = \strtolower( $type->name );

        if ( $from instanceof SplFileInfo ) {
            $from = $this->pathfinder->get( $from, 'dir.assets', true );
        }

        $normalize = \str_replace( ['/', '\\'], DIRECTORY_SEPARATOR, $from );

        // If this is a relative path
        if ( DIRECTORY_SEPARATOR === $normalize[0] ) {
            // Remove leading separator
            $normalize = \ltrim( $normalize, DIRECTORY_SEPARATOR );

            // Remove potential .extension
            $path = \strrchr( $normalize, '.', true ) ?: $normalize;

            // If the asset directory matches the $assetType, trim it to improve consistency
            if ( \str_starts_with( $path, $assetType ) && \str_contains( $path, DIRECTORY_SEPARATOR ) ) {
                $path = \substr( $path, \strpos( $path, DIRECTORY_SEPARATOR ) + 1 );
            }

            // Treat each subsequent directory as a deliminator
            $from = \str_replace( DIRECTORY_SEPARATOR, '.', $path );
        }

        // Prepend the type
        $name = "{$assetType}.{$from}";

        // Replace whitespace and underscores with hyphens to improve consistency
        return (string) \preg_replace( '#[ _]+#', '-', $name );
    }

    /**
     * # ✅
     *
     * Retrieve one or more root asset directories by {@see Type}
     *
     * @param 'document'|'font'|'image'|'root'|'script'|'style'|'video'|Type ...$get
     *
     * @return array<string, FileInfo>
     */
    final public function getAssetDirectories( string|Type ...$get ) : array
    {
        $directories = [];

        // Get by arguments
        if ( $get ) {
            foreach ( $get as $directory ) {
                $key = \strtolower( $directory instanceof Type ? $directory->name : $directory );

                if ( ! \array_key_exists( $key, $this->assetDirectories ) ) {
                    $message = __METHOD__.": Directory '{$key}' is not a valid asset directory.";
                    throw new InvalidArgumentException( $message );
                }

                $directories[$key] = $this->pathfinder->getFileInfo(
                    $this->assetDirectories[$key],
                    assertive : true,
                );
            }
        }
        // Get all
        else {
            foreach ( $this->assetDirectories as $key => $parameter ) {
                $directories[$key] = $this->pathfinder->getFileInfo( $parameter, assertive : true );
            }
        }

        return $directories;
    }

    /**
     * # ✅
     *
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

            // If the path is empty or not a directory, it is invalid
            if ( ! $path || $path->getExtension() ) {
                $result = new InvalidArgumentException( 'Invalid asset directory: '.$directory );

                break;
            }

            // Ensure the directory exists
            if ( ! $path->exists() ) {
                Filesystem::mkdir( $path );
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
     * # ✅
     *
     * Generate a relative path to an asset file.
     *
     * @internal
     *
     * @param string $path
     *
     * @param string $ext
     *
     * @return string
     */
    final protected function relativePublicAsset( string $path, string $ext ) : string
    {
        $relativePath = $this->pathfinder->get( $path, 'dir.assets' );

        if ( ! $relativePath ) {
            $message = 'Invalid asset directory: '.$path;
            throw new InvalidArgumentException( $message );
        }

        return Normalize::url( Str::end( $relativePath, '.'.\trim( $ext, '.' ) ) );
    }

    /**
     * # ✅
     *
     * Generate a relative path to an asset file.
     *
     * @internal
     *
     * @param FileInfo|string $path
     * @param ?string         $ext
     *
     * @return string|string[]
     */
    final protected function relativeAssetPath( string|FileInfo $path, ?string $ext = null ) : string|array
    {
        if ( $path instanceof FileInfo ) {
            if ( $path->isDir() ) {
                $files = [];
                $ext ??= '*';

                foreach ( $path->glob( "/*.{$ext}" ) as $file ) {
                    $files[] = $this->relativeAssetPath( (string) $file );
                }
                return \count( $files ) === 1 ? $files[0] : $files;
            }

            $path = (string) $path;
        }

        $relativePath = $this->pathfinder->get( (string) $path, 'dir.assets' );

        if ( ! $relativePath ) {
            $message = 'Invalid asset directory: '.$path;
            throw new InvalidArgumentException( $message );
        }

        return $relativePath;
    }

    #[Deprecated( '❔' )]
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

    #[Deprecated( '❔' )]
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
}
