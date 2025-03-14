<?php

namespace Core\AssetManager;

use Cache\CachePoolTrait;
use Core\AssetManager\Compiler\AssetValidationTrait;
use Core\AssetManager\Exception\MissingAssetResolverException;
use Core\Interface\LazyService;
use Core\Asset\{ImageAsset, ScriptAsset, StyleAsset, Type};
use Psr\Cache\CacheItemPoolInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Finder\Finder;
use InvalidArgumentException;

class AssetManifest implements LazyService
{
    use CachePoolTrait, AssetValidationTrait;

    /** @var array<string, AssetDefinition> */
    private array $loaded = [];

    /**
     * Maps `reference => name`.
     *
     * @var array<string,string>
     */
    private array $assetMap = [];

    // private readonly EntityRepository $entity;

    final public function __construct(
        private readonly AssetConfig        $config,
        ?CacheItemPoolInterface             $cache,
        protected readonly ?LoggerInterface $logger = null,
        // protected readonly ?EntityManagerInterface $database = null,
    ) {
        $this->assignCacheAdapter( $cache, 'manifest' );
    }

    public function get( string $name ) : AssetDefinition
    {
        $reference = $this->resolveAssetName( $name );

        if ( isset( $this->loaded[$reference] ) ) {
            return $this->loaded[$reference];
        }

        if ( ! $this->hasCache( $name ) ) {
            $this->resolve( $reference );
        }

        $asset = \unserialize( (string) $this->getCache( $name ) );

        \assert( $asset instanceof AssetDefinition );

        $this->loaded[$reference] = $asset;

        return $asset;
    }

    final protected function resolve( string $asset ) : void
    {
        match ( Type::from( $asset ) ) {
            Type::IMAGE  => $this->resolveImage( $asset ),
            Type::STYLE  => $this->resolveStyle( $asset ),
            Type::SCRIPT => $this->resolveScript( $asset ),
            default      => throw new MissingAssetResolverException( $asset ),
        };
    }

    final public function scan( Type $type ) : void
    {
        match ( $type ) {
            Type::IMAGE => $this->resolveImage(),
            default     => throw new InvalidArgumentException(),
        };
    }

    final protected function resolveImage( ?string $asset = null ) : void
    {
        $extensions = Type::IMAGE->extensions();

        foreach ( $this->find( Type::IMAGE ) as $file ) {
            //
            // Filter
            if ( ! \in_array( $file->getExtension(), $extensions ) ) {
                continue;
            }

            $name = $this->getName( $file->getPathname() );

            if ( $asset && $asset !== $name ) {
                continue;
            }

            $this->setCache( $name, \serialize( new ImageAsset( $name, $file->getPathname() ) ) );
        }
    }

    final protected function resolveStyle( ?string $asset = null ) : void
    {
        if ( $asset && $this->config->hasReference( $asset ) ) {
            $reference  = $this->config->getReference( $asset );
            $definition = new StyleAsset(
                $reference->name,
                $reference->getSource(),
                $reference->meta,
            );
            $this->setCache( $reference->name, \serialize( $definition ) );

            return;
        }

        // $extensions = Type::STYLE->extensions();

        foreach ( $this->find( Type::STYLE ) as $file ) {
            dump( $file->getPathname() );
        }

        dd( 'TODO: Loop over all possible Style assets for global discovery scan.' );
    }

    final protected function resolveScript( ?string $asset = null ) : void
    {
        if ( $asset && $this->config->hasReference( $asset ) ) {
            $reference  = $this->config->getReference( $asset );
            $definition = new ScriptAsset(
                $reference->name,
                $reference->getSource(),
                $reference->meta,
            );
            $this->setCache( $reference->name, \serialize( $definition ) );

            return;
        }

        // $extensions = Type::STYLE->extensions();

        foreach ( $this->find( Type::SCRIPT ) as $file ) {
            dump( $file->getPathname() );
        }

        dd( 'TODO: Loop over all possible Script assets for global discovery scan.' );
    }

    /**
     * @param string|string[]|Type $files
     *
     * @return Finder
     */
    private function find( string|array|Type $files ) : Finder
    {
        $directories = $files instanceof Type
                ? $this->config->getReference( $files->name() )->getSource()
                : $files;

        return Finder::create()
            ->files()
            ->in( $directories );
    }

    private function resolveAssetName( string $from ) : string
    {
        // ? Load and cached map
        $this->assetMap ??= ['hash' => 'type.load-asset-map'];

        if ( isset( $this->assetMap[$from] ) ) {
            return $this->assetMap[$from];
        }

        return $this->getName( $from );
    }
}
