<?php

namespace Core\AssetManager\Compiler;

use Core\Asset\Type;
use Core\AssetManager\AssetDefinition;
use Core\AssetManager\Config\AssetRegistration;
use InvalidArgumentException;
use Stringable;
use function Support\{isPath, slug};
use const Support\AUTO;

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

        $type = \strtolower( Type::from( $name )->name );

        if ( \str_starts_with( $name, "{$type}." ) && \ctype_alnum( \str_replace( '.', '', $name ) ) ) {
            return $name;
        }

        if ( isPath( $name ) ) {
            $usePath = \strrchr( $name, '.', true ) ?: $name;

            $usePath = \str_replace( ['\\', '/'], '/', $usePath );

            $fromSource = "assets/{$type}";

            $strpos = \strpos( $usePath, $fromSource ) + \strlen( $fromSource );

            $trimmed = \substr( $usePath, $strpos );

            $name = \trim( \strstr( $trimmed, '/' ) ?: $trimmed, " \n\r\t\v\0/." );
        }

        $name = slug( $name );

        \assert(
            \ctype_alnum( \str_replace( ['.', '-'], '', $name ) ),
            "Asset names must only contain ASCII letters, numbers, periods, and hyphens. '{$from}' provided.",
        );

        return "{$type}.{$name}";
    }

    protected function isAssetID( string $string, ?string &$message = AUTO ) : bool
    {
        if ( \strlen( $string ) === 8 && \ctype_alnum( $string ) ) {
            return true;
        }

        if ( ! $message ) {
            $length  = \strlen( $string );
            $message = "Asset ID must be 16 alphanumeric characters; [{$length}] '{$string}' given";
        }
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
