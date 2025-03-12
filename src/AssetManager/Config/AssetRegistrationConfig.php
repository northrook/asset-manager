<?php

declare(strict_types=1);

namespace Core\AssetManager\Config;

use Core\AssetManager\AssetConfig;
use Core\Asset\Type;
use Core\Pathfinder;
use Core\Pathfinder\Path;
use InvalidArgumentException;
use Symfony\Component\DependencyInjection\Loader\Configurator\ReferenceConfigurator;
use const Support\AUTO;
use function Support\isRelativePath;

/**
 * @internal
 * @author Martin Nielsen <mn@northrook.com>
 */
final class AssetRegistrationConfig
{
    /** @var array<string, AssetRegistration> */
    public array $assets = [];

    /**
     * @param AssetConfig $config
     * @param Pathfinder  $pathfinder
     */
    public function __construct(
        private readonly AssetConfig $config,
        private readonly Pathfinder  $pathfinder,
    ) {
        $this->assets[Type::IMAGE->name()] = new AssetRegistration( Type::IMAGE->name() );
    }

    /**
     * @noinspection PhpPossiblePolymorphicInvocationInspection
     *
     * @param string                                                              $name
     * @param string|string[]                                                     $source
     * @param array<array-key, bool|string>|ReferenceConfigurator|string|string[] $service
     * @param null|bool                                                           $prefersInline
     *
     * @return $this
     */
    public function style(
        string                             $name,
        string|array                       $source = [],
        string|array|ReferenceConfigurator $service = [],
        ?bool                              $prefersInline = AUTO,
    ) : self {
        $name  = $this->assetName( $name, Type::STYLE );
        $asset = $this->assetRegistration( $name );

        /** @var string $path */
        foreach ( (array) $source as $path ) {
            if ( isRelativePath( $path ) ) {
                foreach ( $this->config->assetDirectories as $key ) {
                    $this->pathfinder->quiet = true;

                    $asset->addSource(
                        $this->pathfinder->get( $key.$path ),
                        $key.$path,
                    );
                }
            }
            else {
                $asset->addSource( new Path( $path ) );
            }
        }

        $asset->addService( $service );

        $asset->meta->prefersInline = $prefersInline;

        return $this;
    }

    /**
     * @noinspection PhpPossiblePolymorphicInvocationInspection
     *
     * @param string                                                              $name
     * @param string                                                              $source
     * @param array<array-key, bool|string>|ReferenceConfigurator|string|string[] $service
     * @param bool                                                                $prefersInline
     * @param bool                                                                $mergeImportStatements
     *
     * @return $this
     */
    public function script(
        string                             $name,
        string                             $source,
        string|array|ReferenceConfigurator $service = [],
        bool                               $prefersInline = false,
        bool                               $mergeImportStatements = false,
    ) : self {
        $name  = $this->assetName( $name, Type::SCRIPT );
        $asset = $this->assetRegistration( $name );

        $asset->meta->prefersInline         = $prefersInline;
        $asset->meta->mergeImportStatements = $mergeImportStatements;

        if ( \str_contains( $name, '*' ) ) {
            throw new InvalidArgumentException(
                __METHOD__.' does not support wildcards (*).',
            );
        }

        $path = new Path( $source );

        if ( $path->isRelative() ) {
            foreach ( $this->config->assetDirectories as $key ) {
                $this->pathfinder->quiet = true;
                $asset->addSource(
                    $this->pathfinder->getPath( "{$key}/{$path}" ),
                    $key,
                );
            }
        }
        else {
            $asset->addSource( $path );
        }

        $asset->addService( $service );

        return $this;
    }

    /**
     * @param string                                                              $directoryPath
     * @param ?int                                                                $priority
     * @param array<array-key, bool|string>|ReferenceConfigurator|string|string[] $service
     *
     * @return $this
     */
    public function imageDirectory(
        ?string                            $directoryPath = null,
        ?int                               $priority = AUTO,
        string|array|ReferenceConfigurator $service = [],
    ) : self {
        $asset = $this->assetRegistration( Type::IMAGE->name() );

        $this->pathfinder->quiet = true;

        if ( $directoryPath ) {
            $path = match ( true ) {
                isRelativePath( $directoryPath ) => $this->pathfinder->getPath( "dir.assets/{$directoryPath}" ),
                default                          => $this->pathfinder->getPath( $directoryPath ),
            };

            $asset->addSource( $path, $priority );
        }
        $asset->addService( $service );

        return $this;
    }

    private function assetRegistration( string $name ) : AssetRegistration
    {
        return $this->assets[$name] ?? $this->assets[$name] = new AssetRegistration( $name );
    }

    private function assetName( string $name, Type $type ) : string
    {
        \assert(
            \ctype_alpha( \str_replace( ['.', '-'], '', $name ) ),
            "Asset names must only contain ASCII characters, underscores and dashes. '{$name}' provided.",
        );

        $type = \strtolower( $type->name );
        $name = \strtolower( \trim( $name, '.' ) );

        $fragments = \array_filter( \explode( '.', $name ) );

        if ( $fragments[0] !== $type ) {
            $fragments = [$type, ...$fragments];
        }

        $name = \implode( '.', $fragments );

        \assert(
            \str_contains( $name, '.' ) && \strlen( $name ) > 5,
            "Invalid Asset name '{$name}'. Names must contain at least 5 characters and start with their Type.",
        );

        return $name;
    }
}
