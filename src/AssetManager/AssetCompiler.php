<?php

namespace Core\Service\AssetManager;

use Core\{PathfinderInterface, Service\AssetManager};
use Support\Normalize;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use const Support\AUTO;
use InvalidArgumentException;

#[Autoconfigure( lazy : true )]
class AssetCompiler
{
    private array $assetDirectories = [
        AssetManager::DIR_ASSETS,
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
     */
    public function __construct(
        private readonly PathfinderInterface $pathfinder,
    ) {
    }

    public function prepareAssetDirectories() : void
    {
        $directories = [];

        foreach ( $this->assetDirectories as $directory ) {
            $path = $this->pathfinder->get( $directory );
            dump( $path );
        }

        dump( $directories );
    }

    public function publicAssetPath(
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

    public function publicAssetsUrl(
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
