<?php

namespace Core\AssetManager\Compiler;

use Core\Asset\Type;
use Core\AssetManager\AssetDefinition;
use Core\AssetManager\Config\AssetRegistration;
use InvalidArgumentException;
use Stringable;
use function Support\{isPath, slug};

trait AssetValidationTrait
{
    protected function getName( Stringable|string $from ) : string
    {
        if ( $from instanceof AssetDefinition || $from instanceof AssetRegistration ) {
            return $from->name;
        }

        if ( ! $name = (string) $from ) {
            throw new InvalidArgumentException( 'AssetReference name cannot be empty.' );
        }

        $type = Type::from( $name );

        if ( \str_starts_with( $name, "{$type->name()}." ) && $this->isName( $name ) ) {
            return $name;
        }

        if ( isPath( $name ) ) {
            // Normalize path to name structure
            $usePath = \trim( \str_replace( ['\\', '/'], '/', $name ), " \n\r\t\v\0/" );

            // Remove Extensions
            if ( $extension = \pathinfo( $name, PATHINFO_EXTENSION ) ) {
                $usePath = \substr( $usePath, 0, -\strlen( $extension ) - 1 );
            }

            // Remove ~meta
            $usePath = \strstr( $usePath, '~', true ) ?: $usePath;

            $fromSource = "assets/{$type->name()}";

            $strpos = \strpos( $usePath, $fromSource ) + \strlen( $fromSource );

            $trimmed = \substr( $usePath, $strpos );

            $name = \trim( \strstr( $trimmed, '/' ) ?: $trimmed, " \n\r\t\v\0/." );
        }

        $name = slug( $name );

        \assert(
            $this->isName( $name ),
            "Asset names must only contain ASCII letters, numbers, periods, and hyphens. '{$name}' provided.",
        );

        return "{$type->name()}.{$name}";
    }

    protected function isAssetID( string $string ) : bool
    {
        return \strlen( $string ) === 8 && \ctype_alnum( $string );
    }

    protected function isReferenceHash( string $string ) : bool
    {
        return \strlen( $string ) === 16 && \ctype_alnum( $string );
    }

    protected function isName( string $string ) : bool
    {
        return \ctype_alnum( \str_replace( ['.', '-'], '', $string ) );
    }
}
