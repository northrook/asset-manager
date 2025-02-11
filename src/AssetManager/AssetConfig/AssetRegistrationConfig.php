<?php

namespace Core\AssetManager\AssetConfig;

use Core\AssetManager\{AssetConfig};
use Core\Asset\Type;
use Core\Pathfinder;
use InvalidArgumentException;

final class AssetRegistrationConfig
{
    public array $styles = [];

    /** @var */
    public array $scripts = [];

    /** @var string[] */
    public array $images = [];

    public function __construct( private readonly AssetConfig $config ) {}

    /**
     * @param string          $name
     * @param string|string[] $source
     *
     * @return $this
     */
    public function style(
        string       $name,
        string|array $source,
    ) : self {
        $name = $this->assetName( $name, Type::STYLE );

        foreach ( (array) $source as $path ) {
            $path = new Pathfinder\Path( $path );
            if ( $path->isRelative() ) {
                foreach ( $this->config->assetDirectories as $key ) {
                    $this->styles[$name][] = $this->config
                        ->pathfinder
                        ->get( "{$key}/{$path}" );
                }
            }
            else {
                $this->styles[$name][] = $path->getRealPath();
            }
        }

        return $this;
    }

    public function script(
        string $name,
        string $source,
    ) : self {
        $name = $this->assetName( $name, Type::SCRIPT );

        $path = new Pathfinder\Path( $source );

        if ( $path->isRelative() ) {
            foreach ( $this->config->assetDirectories as $key ) {
                $resolvePath = $this->config->pathfinder->getPath( "{$key}/{$path}" );

                if ( $resolvePath->exists() ) {
                    $path = $resolvePath;

                    break;
                }
            }
        }

        $this->scripts[$name] = $path->getRealPath();

        return $this;
    }

    public function imageDirectory(
        string $directoryPath,
    ) : self {
        if ( \str_starts_with( 'dir.', $directoryPath ) ) {
            $path = $this->config->pathfinder->getPath( $directoryPath );
        }
        else {
            $path = new Pathfinder\Path( $directoryPath );

            if ( $path->isRelative() ) {
                $path = $this->config->pathfinder->getPath( "dir.assets/{$path}" );
            }
        }

        if ( $path->getExtension() ) {
            $message = 'Only directories are accepted.';
            throw new InvalidArgumentException( $message );
        }

        \mkdir( $path->getRealPath(), 0777, true );

        $this->images[] = $path->getRealPath();

        return $this;
    }

    public function resolve() : array
    {
        $assets = [];

        foreach ( $this->styles as $name => $styles ) {
            foreach ( $styles as $style ) {
                if ( ! \str_ends_with( $style, '.css' ) ) {
                    $message
                            = "Asset '{$name}' was provided invalid source '{$style}'. Only '.css' files are accepted.";
                    throw new InvalidArgumentException( $message );
                }
            }
            $assets[$name] = $styles;
        }

        foreach ( $this->scripts as $name => $script ) {
            if ( ! \str_ends_with( $script, '.js' ) ) {
                $message = "Asset '{$name}' was provided invalid source '{$script}'. Only '.css' files are accepted.";
                throw new InvalidArgumentException( $message );
            }
            $assets[$name] = $script;
        }

        foreach ( $this->images as $image ) {
            $assets['images'][] = $image;
        }

        return $assets;
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
