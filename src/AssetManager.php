<?php

declare(strict_types=1);

namespace Core\Service;

use Core\{PathfinderInterface,
        Service\AssetManager\Asset\AssetInterface,
        Service\AssetManager\Asset\AssetModelInterface,
        Service\AssetManager\Asset\AssetReference,
        Service\AssetManager\Asset\Type,
        Service\AssetManager\AssetLocator,
        Service\AssetManager\AssetManifest,
        Service\AssetManager\Exception\InvalidAssetTypeException,
        Service\AssetManager\Interface\AssetManagerInterface,
        Service\AssetManager\Model\ImageAsset,
        Service\AssetManager\Model\ScriptAsset,
        Service\AssetManager\Model\StyleAsset,
        SettingsInterface};
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use Symfony\Contracts\Cache\CacheInterface;
use RuntimeException;

#[Autoconfigure( lazy : true, public : false )]
class AssetManager implements AssetManagerInterface
{
    public const string
        DIR_ASSETS_KEY = 'dir.assets',         // where to find application assets
        DIR_PUBLIC_KEY = 'dir.assets.public',  // publicly available assets
        URL_PUBLIC_KEY = 'url.public',         // application url; example.com
        DIR_BUILD_KEY  = 'dir.assets.build';   // store optimized and cached assets

    public const string
        DIR_ASSETS    = 'dir.assets',
        DIR_STYLES    = 'dir.assets/styles',
        DIR_SCRIPTS   = 'dir.assets/scripts',
        DIR_FONTS     = 'dir.assets/fonts',
        DIR_IMAGES    = 'dir.assets/images',
        DIR_VIDEOS    = 'dir.assets/videos',
        DIR_DOCUMENTS = 'dir.assets/documents';

    /** @var array<string, callable(AssetModelInterface):AssetModelInterface> */
    protected array $assetModelCallback = [];

    /** @var array<string, callable(AssetModelInterface):AssetModelInterface> */
    protected array $assetTypeCallback = [];

    final public function __construct(
        protected readonly AssetManifest       $manifest,
        protected readonly AssetLocator        $locator,
        protected readonly PathfinderInterface $pathfinder,
        protected readonly ?SettingsInterface  $settings = null,
        protected readonly ?CacheInterface     $cache = null,
        protected readonly ?LoggerInterface    $logger = null,
        protected bool                         $lock = false,
    ) {
    }

    final public function assetModelCallback( string $asset, callable $callback ) : void
    {
        if ( $this->lock ) {
            $message = "Unable to add assetModelCallback to '{$asset}', the AssetManager is locked.";
            throw new RuntimeException( $message );
        }
        $this->assetModelCallback[$asset] = $callback;
    }

    final public function assetTypeCallback( Type $type, callable $callback ) : void
    {
        if ( $this->lock ) {
            $message = "Unable to add assetTypeCallback to '{$type->name}', the AssetManager is locked.";
            throw new RuntimeException( $message );
        }
        $this->assetTypeCallback[$type->name] = $callback;
    }

    /**
     * @param string                                    $asset
     * @param ?string                                   $assetID
     * @param array<string, null|bool|float|int|string> $attributes
     * @param bool                                      $nullable   [false] throw by default
     *
     * @return ($nullable is true ? null|AssetInterface : AssetInterface)
     */
    final public function get(
        string  $asset,
        ?string $assetID = null,
        array   $attributes = [],
        bool    $nullable = false,
    ) : ?AssetInterface {
        $assetModel = $this->getAssetModel( $asset, $assetID, $nullable );

        if ( \is_null( $assetModel ) && $nullable ) {
            return null;
        }

        \assert( $assetModel instanceof AssetModelInterface );

        $this->handleAssetCallback( $assetModel );

        // TODO : Handle cache

        $resolved = $assetModel->render( $attributes );

        dump( $assetModel, $resolved );

        return $resolved;
    }

    /**
     * @param AssetReference|string $asset
     * @param ?string               $assetID
     * @param bool                  $nullable [false] throw by default
     *
     * @return ($nullable is true ? null|AssetModelInterface : AssetModelInterface )
     */
    final public function getAssetModel(
        string|AssetReference $asset,
        ?string               $assetID = null,
        bool                  $nullable = false,
    ) : ?AssetModelInterface {
        if ( \is_string( $asset ) ) {
            // TODO: Handle autoDiscover on missing AssetReference
            $asset = $this->manifest->get( $asset );
        }

        \assert( $asset instanceof AssetReference );

        $model = $this->assetModel( $asset->type );

        if ( \is_null( $model ) && $nullable ) {
            return null;
        }

        return $model::fromReference(
            $asset,
            $this->pathfinder,
        )->build( $assetID, $this->settings );
    }

    /**
     * @param Type $from
     * @param bool $nullable [false] throw by default
     *
     * @return ($nullable is true ? null|class-string<AssetModelInterface> : class-string<AssetModelInterface> )
     */
    final protected function assetModel( Type $from, bool $nullable = false ) : ?string
    {
        $assetModel = match ( $from ) {
            Type::STYLE  => StyleAsset::class,
            Type::SCRIPT => ScriptAsset::class,
            Type::IMAGE  => ImageAsset::class,
            default      => null,
        };

        if ( $assetModel ) {
            return $assetModel;
        }

        return $nullable ? null : throw new InvalidAssetTypeException( $from );
    }

    /**
     * Handle registered pre-render `callback` functions.
     *
     * @param AssetModelInterface $assetModel
     *
     * @return void
     */
    private function handleAssetCallback( AssetModelInterface &$assetModel ) : void
    {
        if ( \array_key_exists(
            $assetModel->getType()->name,
            $this->assetTypeCallback,
        ) ) {
            $assetModel = ( $this->assetTypeCallback[$assetModel->getType()->name] )( $assetModel );
        }
        if ( \array_key_exists(
            $assetModel->getName(),
            $this->assetModelCallback,
        ) ) {
            $assetModel = ( $this->assetModelCallback[$assetModel->getName()] )( $assetModel );
        }
    }
}
