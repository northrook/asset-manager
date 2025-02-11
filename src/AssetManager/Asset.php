<?php

namespace Core\AssetManager;

use Core\Asset\Type;

/**
 * @method static register( string $name, string|string[] $source )
 */
abstract class Asset
{
    public const Type TYPE = Type::ABSTRACT;

    /**
     * Asset names must:
     * - be `lower-case.dot.notated`
     * - start with the type: `type.asset.name`
     *
     * @param string $name
     *
     * @return non-empty-lowercase-string
     */
    final public static function name( string $name ) : string
    {
        \assert(
            \ctype_alpha( \str_replace( ['.', '-'], '', $name ) ),
            "Asset names must only contain ASCII characters, underscores and dashes. '{$name}' provided.",
        );

        // Ensure extending class sets the required Type constant
        \assert(
            static::TYPE !== Type::ABSTRACT,
            'Required public class constant '.static::class.'::TYPE not set.',
        );

        $type = \strtolower( static::TYPE->name );
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
