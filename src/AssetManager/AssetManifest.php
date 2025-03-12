<?php

/** @noinspection PhpClassCanBeReadonlyInspection */

namespace Core\AssetManager;

use Cache\CachePoolTrait;
use Core\Asset\{Image, Type};
use Doctrine\ORM\{EntityManagerInterface, EntityRepository};
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\DependencyInjection\Attribute\Lazy;
use Symfony\Component\Finder\Finder;
use function Support\slug;
use const Support\AUTO;
use RuntimeException;

/**
 * @service
 */
#[Lazy]
class AssetManifest
{
    use CachePoolTrait;

    /** @var array<string, AssetDefinition> */
    private array $loaded = [];

    private readonly EntityRepository $entity;

    final public function __construct(
        private readonly AssetConfig               $config,
        CacheItemPoolInterface                     $cache,
        protected readonly ?EntityManagerInterface $database = null,
    ) {
        $this->setCacheAdapter( $cache, 'manifest' );
    }

    public function get( string $name ) : AssetDefinition
    {
        if ( ! $this->hasCache( $name ) ) {
            $this->discover( $name );
        }

        return \unserialize( $this->getCache( $name ) );
    }

    final public function discover(
        ?string $name = null,
        ?Type   $type = null,
    ) : void {
        $config ??= $this->config;

        // Discover all
        if ( ! $name && ! $type ) {
            $this->scan( $config );
        }

        if ( $name ) {
            $type ??= Type::from( $name );
            $name = $this->nameFromPath( $name, $type );
        }

        if ( $type === Type::IMAGE ) {
            $this->scanImages( $config );
            return;
        }

        if ( $config->hasReference( $name ) ) {
            // It is the AssetManagers job to resolve path-to-reference
        }

        dump( \get_defined_vars() );
    }

    final public function scan( ?AssetConfig $config = AUTO ) : void
    {
        $scanned = [];
        $config ??= $this->config;

        $assets = $config->resolve();

        foreach ( $assets as $registration ) {
            match ( $registration->type ) {
                Type::IMAGE => $this->scanImages( $config ),
                default     => null,
            };
        }
        // dump( $assets );
    }

    final public function scanImages( AssetConfig $config ) : void
    {
        $type        = Type::IMAGE;
        $directory   = $config->resolve()['image']->getSource();
        $extenstions = $type->extensions();

        $finder = Finder::create()
            ->files()
            ->in( $directory );

        foreach ( $finder as $file ) {
            $ext = $file->getExtension();

            if ( ! \in_array( $ext, $extenstions ) ) {
                continue;
            }

            $name = $this->nameFromPath( $file->getPathname(), $type );
            $path = $file->getPathname();

            $asset = new Image( $name, $path );

            $this->setCache( $name, \serialize( $asset ) );
            // $this->loaded[$name] = ;
        }
    }

    // final protected function getRepository() : EntityRepository
    // {
    //     if ( ! $this->database ) {
    //         throw new RuntimeException( 'No Database' );
    //     }
    //     return $this->entity ??= $this->database->getRepository( AssetReference::class );
    // }

    private function nameFromPath( string $path, Type $type ) : string
    {
        $usePath = \str_replace( ['\\', '/'], '/', $path );

        if ( ! \str_contains( $usePath, '/' ) ) {
            return $path;
        }

        $usePath    = \strrchr( $usePath, '.', true ) ?: $usePath;
        $typeName   = $type->name();
        $fromSource = "assets/{$typeName}";

        $strpos = \strpos( $usePath, $fromSource ) + \strlen( $fromSource );

        $trimmed = \substr( $usePath, $strpos );

        $name = \trim( \strstr( $trimmed, '/' ) ?: $trimmed, " \n\r\t\v\0/." );

        if ( \str_contains( $name, '/' ) ) {
            [$dir, $name] = \explode( '/', $name, 2 );
            $dir          = \str_replace( ['\\', '/'], '.', $dir );
            $assetName    = "{$typeName}.{$dir}.".slug( $name );
        }
        else {
            $assetName = "{$typeName}.".slug( $name );
        }

        \assert(
            \ctype_alnum( \str_replace( ['.', '-'], '', $assetName ) ),
            "AssetReference names must only contain ASCII letters, numbers, periods, and hyphens. '{$assetName}' provided.",
        );

        return $assetName;
    }
}
