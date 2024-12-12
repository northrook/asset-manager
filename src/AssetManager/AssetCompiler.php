<?php

namespace Core\Service\AssetManager;

use Core\{PathfinderInterface,
    Service\AssetManager,
    Service\AssetManager\Asset\AssetModel,
    Service\AssetManager\Asset\Type,
    Service\AssetManager\Model\AssetModelInterface
};
use Psr\Log\LoggerInterface;
use Support\Normalize;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use Symfony\Component\Filesystem\Filesystem;
use const Support\AUTO;
use InvalidArgumentException;

#[Autoconfigure( lazy : true )]
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

    /**
     * @param PathfinderInterface $pathfinder
     * @param ?LoggerInterface    $logger
     */
    public function __construct(
        private readonly PathfinderInterface $pathfinder,
        private readonly ?LoggerInterface    $logger,
    ) {
        // Reset the Filesystem on initialization
        AssetCompiler::$filesystem = null;
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

    /**
     * @param string $inDirectory
     *
     * @return AssetModelInterface[]
     */
    public function discoverStyleAssets( string $inDirectory ) : array
    {
        $styles = [];

        foreach ( ( new Compiler\AssetScanner( $inDirectory, Type::STYLE ) ) as $style ) {
            // $styles[] = new
            dump( $style );
        }

        // array contains file.css and dir/*.css
        // if example.css and example/*.css exists,
        // combine as if [ ... $example, $example ]

        return $styles;
    }

    /**
     * @param string $inDirectory
     *
     * @return AssetModelInterface[]
     */
    public function discoverScriptAssets( string $inDirectory ) : array
    {
        $scripts = [];

        // foreach ( $this->scanDirectory( $inDirectory, Type::SCRIPT ) as $asset ) {
        // }

        // array contains file.css and dir/*.js
        // if example.css and example/*.js exists,
        // combine as if [ ... $example, $example ]
        // look into how AssetMapper handles this at some point

        return $scripts;
    }

    /**
     * @param string $inDirectory
     *
     * @return AssetModelInterface[]
     */
    public function discoverFontAssets( string $inDirectory ) : array
    {
        $fonts = $this->scanDirectory( $inDirectory, Type::FONT );

        return $fonts;
    }

    /**
     * @param string $inDirectory
     *
     * @return AssetModelInterface[]
     */
    public function discoverImageAssets( string $inDirectory ) : array
    {
        $images = $this->scanDirectory( $inDirectory, Type::IMAGE );

        return $images;
    }

    /**
     * @param string $inDirectory
     *
     * @return AssetModelInterface[]
     */
    public function discoverVideoAssets( string $inDirectory ) : array
    {
        $videos = $this->scanDirectory( $inDirectory, Type::VIDEO );

        return $videos;
    }

    /**
     * @param string $inDirectory
     *
     * @return AssetModelInterface[]
     */
    public function discoverDocumentAssets( string $inDirectory ) : array
    {
        $documents = $this->scanDirectory( $inDirectory, Type::DOCUMENT );

        return $documents;
    }

    /**
     * @param string $inDirectory
     *
     * @return AssetModelInterface[]
     */
    public function discoverAssets( string $inDirectory ) : array
    {
        $any = $this->scanDirectory( $inDirectory );

        return $any;
    }

    /**
     * Retrieve one or more root asset directories by {@see Type}
     *
     * @param 'document'|'font'|'image'|'root'|'script'|'style'|'video'|Type ...$scan
     *
     * @return void
     */
    final public function scanDirectories( string|Type ...$scan ) : void
    {
        $found = [];

        foreach ( $this->getAssetDirectories( ...$scan ) as $key => $directory ) {
            $type = Type::from( $key ) ?? $key;
            $path = $this->pathfinder->get( $directory );

            $found[$key] = match ( $type ) {
                Type::STYLE    => $this->discoverStyleAssets( $path ),
                Type::SCRIPT   => $this->discoverScriptAssets( $path ),
                Type::FONT     => $this->discoverFontAssets( $path ),
                Type::IMAGE    => $this->discoverImageAssets( $path ),
                Type::VIDEO    => $this->discoverVideoAssets( $path ),
                Type::DOCUMENT => $this->discoverDocumentAssets( $path ),
                default        => $this->discoverAssets( $path ),
            };
        }

        if ( $found ) {
            dump( $found );
        }
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
     * @return void
     *
     * @throws InvalidArgumentException if a directory is `invalid` or a `file`
     */
    final public function prepareAssetDirectories() : void
    {
        foreach ( $this->assetDirectories as $key => $directory ) {
            $path = $this->pathfinder->getFileInfo( $directory );

            if ( ! $path ) {
                throw new InvalidArgumentException( 'Invalid asset directory: '.$directory );
            }

            // Ensure the directory exists
            if ( $path->exists() ) {
                $this->filesystem()->mkdir( $path );
            }

            // Ensure the set $path is a readable directory.
            if ( $path->isFile() || ! $path->isReadable() ) {
                throw new InvalidArgumentException();
            }
        }
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

    /**
     * @param string    $scan
     * @param null|Type $type
     *
     * @return array<string, array{name: string, type: string, path: list<string>|string}>
     */
    private function scanDirectory( string $scan, ?Type $type = null ) : array
    {
        $found = [];

        $directory
                = $this->pathfinder->get( $scan ) ?? throw new InvalidArgumentException( 'Directory path is invalid.' );

        $scanType = $type ? '/**' : '/*.*';

        foreach ( \glob( "{$directory}$scanType" ) ?: [] as $file ) {
            $fileInfo = new \Support\FileInfo( $file );

            if ( ! $fileInfo->getType() ) {
                $this->logger?->warning(
                    'Could not determine type for file: {file}.',
                    ['file' => $file],
                );

                continue;
            }

            if ( $fileInfo->isDir() ) {
                $path = [];

                foreach ( \glob( "{$fileInfo}/*.*" ) ?: [] as $subDirectory ) {
                    $segmentFileInfo = new \Support\FileInfo( $subDirectory );
                    if ( $type ) {
                        if ( Type::from( $segmentFileInfo->getExtension() ) !== $type ) {
                            $this->logger?->warning( 'Unexpected file type in asset directory.' );
                        }
                        else {
                            $path[] = $segmentFileInfo->getRealPath();
                        }
                    }
                    else {
                        $path[] = $segmentFileInfo->getRealPath();
                    }
                }
            }
            else {
                $path = $fileInfo->getRealPath();
            }

            if ( ! $path ) {
                continue;
            }

            $found[$fileInfo->getFilename()] = [
                'name' => $fileInfo->getFilename(),
                'type' => $fileInfo->getType(),
                'path' => $path,
            ];
        }
        return $found;
    }
}
