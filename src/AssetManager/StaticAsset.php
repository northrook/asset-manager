<?php

declare( strict_types = 1 );

namespace Northrook;

// For Stylesheets and Scripts

use Northrook\AssetManager;
use Northrook\Core\Trait\PropertyAccessor;
use Northrook\Core\Trait\StaticClass;
use Northrook\Support\File;
use Northrook\Support\Str;
use function Northrook\Core\Function\classBasename;
use function Northrook\Core\Function\hashKey;
use function Northrook\Core\Function\isUrl;
use function Northrook\Core\Function\normalizeKey;
use function Northrook\Core\Function\normalizePath;

/**
 * @property-read string $assetID
 * @property-read string $assetType
 */
abstract class StaticAsset extends Asset
{
    use PropertyAccessor;

    protected readonly File $publicFile;
    protected array         $attributes = [];

    public function __get( string $property ) {
        return match ( $property ) {
            'assetID'   => $this->assetID,
            'assetType' => $this->assetType,
        };
    }


    /**
     * Build the asset at any time, calls {@see build()} and assigns the return string to {@see $html}.
     *
     * @return void
     */
    final protected function buildAsset() : void {
        $this->html = $this->cachedAsset();
    }

    final protected function publicUrl() : string {
        $path = \substr( $this->publicFile->path, \strlen( AssetManager::get()->publicRootDirectory ) );
        return '/' . \ltrim( \str_replace( '\\', '/', $path ), '/' ) . $this->publicAssetVersion();
    }

    final protected function storeRemoteAssetFile( string $url, string $filetype, ?string $location = null ) : File {
        $location ??= $this->manager()->assetStorageDirectory . '/remote/' . $this->assetType;
        $filename = Str::end( $this::generateFilenameKey( $url ), '.' . trim( $filetype, ". \n\r\t\v\0" ) );

        $localAssetFile = new File ( "$location/$filename" );
        File::copy( $url, $localAssetFile->path );
        return $localAssetFile;
    }

    /**
     * - If no {@see $directory} is provided, one will be generated from the className.
     *
     * @param string   $source
     * @param ?string  $directory  [lowercased] /public/assets/$directory
     *
     * @return File
     */
    final protected function publicAssetFile( string $source, ?string $directory = null ) : File {

        $rootDir   = AssetManager::get()->projectRootDirectory;
        $vendorDir = normalizePath( $rootDir . DIRECTORY_SEPARATOR . 'vendor' );

        $source = match ( isUrl( $source ) ) {
            true  => $this->storeRemoteAssetFile( $source, 'js' ),
            false => new File( $source ),
        };

        $directory = strtolower( $directory ?? $this->assetType );

        $asset = [
            'base'      => AssetManager::get()->publicAssetsDirectory,
            'directory' => $directory,
        ];

        // Parse the bundle name if the $source is from a Composer package
        if ( \str_starts_with( $source->path, $vendorDir ) ) {
            // Remove until the /vendor/ directory
            $bundle = \substr( $source->path, \strlen( $vendorDir ) + 1 );
            // Remove the package vendor directory
            $bundle = \substr( $bundle, \strpos( $bundle, '\\' ) + 1 );
            // Retrieve only the package directory
            $bundle = \strstr( $bundle, '\\', true );
            // Remove superfluous naming from the package directory
            $bundle = \trim( \str_replace( [ 'symfony', 'bundle' ], '', $bundle ), '-' );

            // Add the package directory as a bundle subdirectory
            $asset[ 'bundle' ] = $bundle;
        }

        $asset[ 'filename' ] = $source->basename;

        $public = new File( $asset );

        if ( $source->isFile ) {
            $source->copyTo( $public->path, true );
        }


        return $this->publicFile = $public;
    }

    protected function publicAssetVersion() : string {
        return "?v={$this->publicFile->lastModified}";
    }

    protected function attributes(
        ?array $add = null,
        ?array $set = null,
    ) : array {

        $this->attributes[ 'id' ] = "asset-{$this->assetType}-" . pathinfo( $this->publicUrl(), PATHINFO_FILENAME );

        if ( $add ) {
            $this->attributes += $add;
        }

        if ( $set ) {
            $this->attributes = array_merge( $this->attributes, $set );
        }

        return $this->attributes;
    }
}