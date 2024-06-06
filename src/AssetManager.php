<?php

namespace Northrook\Asset;

use InvalidArgumentException;
use LogicException;
use Northrook\Support\Str\PathFunctions;
use Northrook\Type\Path;
use Northrook\Logger\Log;
use Symfony\Component\Cache\Adapter\AdapterInterface;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Component\Cache\Adapter\PhpFilesAdapter;
use Symfony\Component\Cache\Exception\CacheException;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\VarExporter\Exception\ExceptionInterface;
use Symfony\Component\VarExporter\VarExporter;

class AssetManager
{
    use PathFunctions;

    /** Default Options for the {@see AssetManager} */
    private const OPTIONS = [
        'cacheKey'       => 'assets', // Default cache key
        'cacheTtl'       => 0,        // Default cache TTL, in seconds
        'onOPcacheError' => 'log',    // ignore|log|throw
    ];

    /** Singleton {@see AssetManager} instance */
    private static AssetManager $instance;

    private readonly string $assetManifestPath;
    private readonly array  $options;


    /** @var array<string,array> */
    private array $inventory;
    private int   $updated   = 0;

    public readonly string $projectRoot;
    public readonly string $publicRoot;
    public readonly string $publicAssets;


    // private readonly Cache $cache;
    public readonly string           $cachePath;
    public readonly AdapterInterface $cache;

    public function __construct(
        string            $projectRoot,
        string            $publicRoot,
        string            $publicAssets,
        string            $cachePath,
        string            $assetManifestPath,
        ?AdapterInterface $cache = null,
        ?array            $inventory = null,
        array             $options = [],
    ) {
        // Ensure the Asset Manager is not instantiated twice
        if ( isset( AssetManager::$instance ) ) {
            throw new LogicException( 'The Asset Manager has already been instantiated.' );
        }

        // Assign options
        $this->options = array_merge( AssetManager::OPTIONS, $options );

        // Assign paths
        $this->assetManifestPath = $this::normalizePath( $assetManifestPath );
        $this->projectRoot       = $this::normalizePath( $projectRoot );
        $this->publicRoot        = $this::normalizePath( $publicRoot );
        $this->publicAssets      = $this::normalizePath( $publicAssets );
        $this->cachePath         = $this::normalizePath( $cachePath );

        // Load the Inventory
        $this->inventory = $this->assetInventory();

        // Assign the cache adapter
        $this->cache = $this->assetCache( $cache );

        AssetManager::$instance = $this;
    }

    /**
     * Get the {@see AssetManager} instance.
     *
     * @return AssetManager
     */
    public static function get() : AssetManager {
        return AssetManager::$instance ?? throw new LogicException(
            'The Asset Manager has not been instantiated. Call new Manager(...) first.',
        );
    }

    /**
     * Get the {@see AssetManager} cache adapter.
     *
     * @return AdapterInterface
     */
    public static function cache() : AdapterInterface {
        return AssetManager::get()->cache;
    }

    final public function asset(
        string $class,      // The className of the asset, so we can instantiate it if needed
        array  $meta,       // Contains the asset's metadata, like the type, description, etc.
               ...$_
    ) : void {

        $id = $meta[ 'assetID' ];

        $asset = $this->inventory[ 'assets' ][ $id ] ?? false;

        // Store a copy of each asset in var/assets/assetID/filename.timestamp.ext
        // This asset may be optimised when lossless compression is used
        // In the manifest below, we reference each asset by date
        // Keep a talley of the history, and allow for a waning when a given asset exceeds a certain depth of history
        // May be worth keeping a history of the manifest as well
        // Not sure how to detect where each asset is actively used, could be simple as an array with "last seen" urls and timestamps
        // They could expire over time, and an option to actively scan all templates be the more precise option when required

        $this->inventory[ 'assets' ][ $id ] = [
            ... $meta,
            'class'     => $class,
            ... $_,
            'timestamp' => time(),
        ];
        $this->updated++;
    }


    /**
     * Will assign the provided {@see AdapterInterface} to the asset cache.
     *
     * If no adapter is provided, a {@see PhpFilesAdapter} will be used.
     * The PhpFilesAdapter requires the PHP extension OPcache to be installed and activated.
     *
     * If OPcache is not available, {@see Manager::$options} `onOPcacheError` will be used eiter:
     * - Fall back to a {@see FilesystemAdapter}.
     *    - With or without an {@see Log::Error} message.
     * - Throw a {@see LogicException}.
     *
     * @param ?AdapterInterface  $cache
     *
     * @return AdapterInterface
     */
    private function assetCache( ?AdapterInterface $cache ) : AdapterInterface {
        try {
            $cache ??= new PhpFilesAdapter(
                $this->options[ 'cacheKey' ],
                $this->options[ 'cacheTtl' ],
                $this->cachePath,
            );
        }
        catch ( CacheException $exception ) {
            if ( $this->options[ 'onOPcacheError' ] === 'log' ) {
                Log::Error(
                    'Could not assign {adapter}, {requirement} not available. Ensure the {requirement} PHP extension is installed and activated.',
                    [
                        'adapter'     => 'PhpFilesAdapter',
                        'requirement' => 'OPcache',
                    ],
                );
            }
            elseif ( $this->options[ 'onOPcacheError' ] === 'throw' ) {
                throw new LogicException(
                    message  : 'Could not assign PhpFilesAdapter, OPcache is not available. Ensure the PHP extension is installed and activated.',
                    code     : 510,
                    previous : $exception,
                );
            }
        }

        return $cache ?? new FilesystemAdapter();
    }


    private function assetInventory() : array {

        $inventory = new Path( $this->assetManifestPath );

        if ( $inventory->exists ) {
            return require $inventory->value;
        }

        $manifest = [
            'generated' => time(),
            'updated'   => 'never',
            'count'     => count( $this->inventory ),
            'assets'    => $this->inventory,
        ];

        $this->updateAssetInventoryManifest(
            $inventory->value,
            $manifest,
        );

        return $manifest;
    }

    public static function updateAssetInventory() : void {
        $manager = AssetManager::get();

        if ( $manager->updated === 0 ) {
            return;
        }

        $manager->inventory[ 'count' ] = count( $manager->inventory[ 'assets' ] );
        $manager->inventory[ 'updated' ] = time();

        $manager->updateAssetInventoryManifest(
            ( new Path( $manager->assetManifestPath ) )->value,
            $manager->inventory,
        );

        Log::Info(
            'Updated the asset inventory.',
            [
                'count' => $manager->inventory[ 'count' ],
            ],
        );
    }

    private function updateAssetInventoryManifest( string $path, array $manifest ) : bool {
        try {
            $inventory = VarExporter::export( $manifest );
        }
        catch ( ExceptionInterface $e ) {
            throw new InvalidArgumentException(
                message  : 'Unable to export the asset manifest.',
                code     : 500,
                previous : $e,
            );
        }
        $assetManager = $this::class;

        $content = <<<PHP
            <?php // source: $assetManager

            /*---------------------------------------------------------------------

                This file is automatically generated by the Asset Manager.

                Do not edit it manually.

                See https://github.com/northrook/asset-manager for more information.

            ---------------------------------------------------------------------*/

            return $inventory;
            PHP;

        try {
            ( new Filesystem() )->dumpFile( $path, $content );
        }
        catch ( IOException $e ) {
            Log::Error(
                'Unable to update the asset manifest.',
                [
                    'path'     => $path,
                    'message'  => $e->getMessage(),
                    'code'     => $e->getCode(),
                    'previous' => $e->getPrevious(),
                ],
            );

            return false;
        }

        return true;
    }
}